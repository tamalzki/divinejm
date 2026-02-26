<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\FinishedProduct;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{
    /**
     * Display a listing of sales
     */
    public function index(Request $request)
    {
        $query = Sale::with(['branch', 'items.finishedProduct', 'user'])
            ->orderBy('sale_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('dr_number')) {
            $query->where('dr_number', 'like', '%' . $request->dr_number . '%');
        }

        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', '%' . $request->customer_name . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }

        $sales = $query->paginate(20);
        $branches = Branch::orderBy('name')->get();

        return view('sales.index', compact('sales', 'branches'));
    }

    /**
     * Show the form for creating a new sale
     */
    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        return view('sales.create', compact('branches'));
    }

    /**
     * Store a newly created sale
     */
    public function store(Request $request)
    {
        // Check for duplicate DR first
        $existingSale = Sale::where('branch_id', $request->branch_id)
            ->where('customer_name', $request->customer_name)
            ->where('dr_number', $request->dr_number)
            ->first();

        if ($existingSale) {
            return redirect()->route('sales.show', $existingSale)
                ->with('error', 'This DR# already has a sale recorded. You can edit the payment status below.');
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'customer_name' => 'required|string',
            'dr_number' => 'required|string',
            'sale_date' => 'required|date',
            'payment_status' => 'nullable|in:paid,to_be_collected',
            'payment_mode' => 'nullable|in:cash,gcash,cheque,bank_transfer,other',
            'payment_reference' => 'nullable|string',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.finished_product_id' => 'required|exists:finished_products,id',
            'items.*.batch_number' => 'nullable|string',
            'items.*.quantity_deployed' => 'nullable|numeric|min:0',
            'items.*.quantity_sold' => 'required|numeric|min:0',
            'items.*.quantity_unsold' => 'nullable|numeric|min:0',
            'items.*.quantity_bo' => 'nullable|numeric|min:0',
            'items.*.quantity_replaced' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            // Create the sale
            $sale = Sale::create([
                'branch_id' => $request->branch_id,
                'customer_name' => $request->customer_name,
                'dr_number' => $request->dr_number,
                'sale_date' => $request->sale_date,
                'amount_paid' => $request->amount_paid ?? 0,
                'payment_status' => $request->payment_status ?? 'to_be_collected', // Default if not set
                'payment_mode' => $request->payment_mode,
                'payment_reference' => $request->payment_reference,
                'payment_date' => $request->payment_date,
                'notes' => $request->notes,
                'user_id' => Auth::id(),
            ]);

            // Create sale items
            foreach ($request->items as $itemData) {
                // Skip if no quantity sold
                if (empty($itemData['quantity_sold']) || $itemData['quantity_sold'] <= 0) {
                    continue;
                }

                $item = SaleItem::create([
                    'sale_id' => $sale->id,
                    'finished_product_id' => $itemData['finished_product_id'],
                    'batch_number' => $itemData['batch_number'] ?? null,
                    'quantity_deployed' => $itemData['quantity_deployed'] ?? 0,
                    'quantity_sold' => $itemData['quantity_sold'],
                    'quantity_unsold' => $itemData['quantity_unsold'] ?? 0,
                    'quantity_bo' => $itemData['quantity_bo'] ?? 0,
                    'quantity_replaced' => $itemData['quantity_replaced'] ?? 0,
                    'unit_price' => $itemData['unit_price'],
                    'notes' => $itemData['notes'] ?? null,
                ]);

                // CRITICAL: Deduct sold quantity from branch inventory
                $inventory = BranchInventory::where('branch_id', $request->branch_id)
                    ->where('finished_product_id', $itemData['finished_product_id'])
                    ->where(function ($query) use ($itemData) {
                        if (!empty($itemData['batch_number'])) {
                            $query->where('batch_number', $itemData['batch_number']);
                        } else {
                            $query->whereNull('batch_number');
                        }
                    })
                    ->lockForUpdate()   // 🔥 ADD THIS LINE
                    ->first();

                if ($inventory) {
                    // Deduct sold + BO quantities from branch inventory
                    $totalDeducted = $itemData['quantity_sold'] + ($itemData['quantity_bo'] ?? 0);
                        if ($inventory->quantity < $totalDeducted) {
                            throw new \Exception(
                                "Insufficient inventory for " .
                                $inventory->finishedProduct->name .
                                ". Available: " .
                                $inventory->quantity .
                                ", Needed: " .
                                $totalDeducted
                            );
                        }
                        $inventory->quantity -= $totalDeducted;
                        $inventory->save();

                    // Record stock movement for sale
                    StockMovement::create([
                        'branch_id' => $request->branch_id,
                        'finished_product_id' => $itemData['finished_product_id'],
                        'batch_number' => $itemData['batch_number'] ?? null,
                        'movement_type' => 'sale',
                        'quantity' => $itemData['quantity_sold'],
                        'movement_date' => $request->sale_date,
                        'reference_number' => $request->dr_number,
                        'notes' => "Sale to {$request->customer_name}",
                        'user_id' => Auth::id(),
                    ]);

                    // Record BO movement if any
                    if (!empty($itemData['quantity_bo']) && $itemData['quantity_bo'] > 0) {
                        StockMovement::create([
                            'branch_id' => $request->branch_id,
                            'finished_product_id' => $itemData['finished_product_id'],
                            'batch_number' => $itemData['batch_number'] ?? null,
                            'movement_type' => 'return_bo',
                            'quantity' => $itemData['quantity_bo'],
                            'movement_date' => $request->sale_date,
                            'reference_number' => $request->dr_number,
                            'notes' => "Bad Order - " . ($itemData['notes'] ?? 'No reason specified'),
                            'user_id' => Auth::id(),
                        ]);
                    }
                } else {
                    throw new \Exception("Product not found in branch inventory: Finished Product ID {$itemData['finished_product_id']}, Batch: {$itemData['batch_number']}");
                }
            }

            // Add customer to branch if not exists
            $branch = Branch::find($request->branch_id);
            $branch->addCustomer($request->customer_name);
        });

        return redirect()->route('sales.index')->with('success', 'Sale recorded successfully!');
    }

    /**
     * Display the specified sale
     */
    public function show(Sale $sale)
    {
        $sale->load(['branch', 'items.finishedProduct', 'user']);
        return view('sales.show', compact('sale'));
    }

    /**
     * Show the form for editing the specified sale
     */
    public function edit(Sale $sale)
    {
        $branches = Branch::orderBy('name')->get();
        $sale->load('items.finishedProduct');
        return view('sales.edit', compact('sale', 'branches'));
    }

    /**
     * Update the specified sale
     */
    public function update(Request $request, Sale $sale)
    {
        $request->validate([
            'sale_date' => 'required|date',
            'payment_mode' => 'nullable|in:cash,gcash,cheque,bank_transfer,other',
            'payment_reference' => 'nullable|string',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $sale->update([
            'sale_date' => $request->sale_date,
            'amount_paid' => $request->amount_paid ?? 0,
            'payment_mode' => $request->payment_mode,
            'payment_reference' => $request->payment_reference,
            'payment_date' => $request->payment_date,
            'notes' => $request->notes,
        ]);

        return redirect()->route('sales.show', $sale)->with('success', 'Sale updated successfully!');
    }

    /**
     * Remove the specified sale
     */
    public function destroy(Sale $sale)
    {
        DB::transaction(function () use ($sale) {
            // Return quantities to inventory
            foreach ($sale->items as $item) {
               $inventory = BranchInventory::where('branch_id', $sale->branch_id)
                    ->where('finished_product_id', $item->finished_product_id)
                    ->where(function ($query) use ($item) {
                        if (!empty($item->batch_number)) {
                            $query->where('batch_number', $item->batch_number);
                        } else {
                            $query->whereNull('batch_number');
                        }
                    })
                    ->lockForUpdate()   // optional but recommended
                    ->first();

                if ($inventory) {
                    $totalReturned = $item->quantity_sold + $item->quantity_bo;
                    $inventory->quantity += $totalReturned;
                    $inventory->save();
                }
            }

            $sale->delete();
        });

        return redirect()->route('sales.index')->with('success', 'Sale deleted successfully!');
    }

    /**
     * API: Get customers for a branch
     */
    public function getCustomers(Branch $branch)
    {
        $customers = $branch->customers_list ?? [];
        
        // Handle if customers is an array of objects (extract name field)
        if (!empty($customers) && is_array($customers)) {
            $customers = array_map(function($customer) {
                // If customer is an object/array with a name field, extract it
                if (is_array($customer) && isset($customer['name'])) {
                    return $customer['name'];
                } elseif (is_object($customer) && isset($customer->name)) {
                    return $customer->name;
                }
                // Otherwise return as-is (assuming it's already a string)
                return $customer;
            }, $customers);
        }
        
        return response()->json([
            'customers' => array_values($customers) // Re-index array
        ]);
    }

    /**
     * API: Get DR numbers deployed to this branch
     */
    public function getDRNumbers(Branch $branch)
    {
        // Get unique DR numbers from stock movements (transfer_out)
        $drNumbers = StockMovement::where('branch_id', $branch->id)
            ->whereIn('movement_type', ['transfer_out', 'extra_free'])
            ->select('reference_number as dr_number')
            ->selectRaw('COUNT(DISTINCT finished_product_id) as product_count')
            ->whereNotNull('reference_number')
            ->groupBy('reference_number')
            ->orderBy('reference_number', 'desc')
            ->get();

        return response()->json([
            'dr_numbers' => $drNumbers
        ]);
    }

    /**
     * API: Get products for a specific DR number
     */
    public function getDRProducts(Branch $branch, $drNumber)
    {
        // Get products from stock movements for this DR
        $movements = StockMovement::with('finishedProduct')
            ->where('branch_id', $branch->id)
            ->where('reference_number', $drNumber)
            ->whereIn('movement_type', ['transfer_out', 'extra_free'])
            ->get();

        $products = $movements->map(function($movement) {
            return [
                'finished_product_id' => $movement->finished_product_id,
                'product_name' => $movement->finishedProduct->name,
                'sku' => $movement->finishedProduct->sku,
                'batch_number' => $movement->batch_number, // Ensure batch number is always set
                'deployed_qty' => $movement->quantity,
                'movement_type' => $movement->movement_type,
                'selling_price' => $movement->finishedProduct->selling_price ?? 0, // Add selling price
            ];
        });

        // Group by product and batch to combine transfer_out and extra_free
        $groupedProducts = $products->groupBy(function($item) {
            return $item['finished_product_id'] . '_' . ($item['batch_number'] ?? 'no-batch');
        })->map(function($group) {
            $first = $group->first();
            return [
                'finished_product_id' => $first['finished_product_id'],
                'product_name' => $first['product_name'],
                'sku' => $first['sku'],
                'batch_number' => $first['batch_number'],
                'deployed_qty' => $group->sum('deployed_qty'),
                'selling_price' => $first['selling_price'],
            ];
        })->values();

        return response()->json([
            'products' => $groupedProducts
        ]);
    }

    /**
     * API: Get products available in branch
     */
    public function getProducts(Request $request, Branch $branch, $customerName)
    {
        // Get all products in branch inventory
        $products = BranchInventory::with('finishedProduct')
            ->where('branch_id', $branch->id)
            ->where('quantity', '>', 0)
            ->get()
            ->map(function($item) {
                return [
                    'finished_product_id' => $item->finished_product_id,
                    'product_name' => $item->finishedProduct->name,
                    'sku' => $item->finishedProduct->sku,
                    'batch_number' => $item->batch_number,
                    'available_qty' => $item->quantity,
                ];
            });

        return response()->json([
            'products' => $products
        ]);
    }

    /**
     * API: Check if DR already exists
     */
    public function checkDrNumber(Request $request, Branch $branch, $customerName, $drNumber)
    {
        $existingSale = Sale::where('branch_id', $branch->id)
            ->where('customer_name', $customerName)
            ->where('dr_number', $drNumber)
            ->with('items.finishedProduct')
            ->first();

        return response()->json([
            'exists' => !is_null($existingSale),
            'sale' => $existingSale
        ]);
    }
}