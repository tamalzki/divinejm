<?php

namespace App\Http\Controllers;

use App\Models\DailyProductionEntry;
use App\Models\DailyProductionIngredient;
use App\Models\DailyProductionReport;
use App\Models\FinishedProduct;
use App\Models\PackerReport;
use App\Models\RawMaterial;
use App\Services\ProductionPackingSyncService;
use App\Support\RawMaterialUnit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DailyProductionController extends Controller
{
    public function __construct(protected ProductionPackingSyncService $productionPackingSyncService) {}

    public function index(Request $request)
    {
        $query = DailyProductionReport::query()
            ->select('daily_production_reports.*')
            ->selectSub(
                DailyProductionEntry::selectRaw('COUNT(*)')
                    ->whereColumn('daily_production_report_id', 'daily_production_reports.id'),
                'lines_count'
            )
            ->selectSub(
                DailyProductionEntry::selectRaw('COALESCE(SUM(actual_yield), 0)')
                    ->whereColumn('daily_production_report_id', 'daily_production_reports.id'),
                'total_actual_yield'
            )
            ->selectSub(
                DailyProductionEntry::selectRaw('COALESCE(SUM(packed_quantity), 0)')
                    ->whereColumn('daily_production_report_id', 'daily_production_reports.id'),
                'total_packed_quantity'
            )
            ->selectSub(
                DailyProductionEntry::selectRaw('COALESCE(SUM(unpacked), 0)')
                    ->whereColumn('daily_production_report_id', 'daily_production_reports.id'),
                'total_unpacked_quantity'
            );

        if ($request->filled('from')) {
            $query->whereDate('production_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('production_date', '<=', $request->input('to'));
        }

        if (($search = trim((string) $request->input('search', ''))) !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', '%'.$search.'%');
                if (ctype_digit($search)) {
                    $q->orWhere('daily_production_reports.id', (int) $search);
                }
                $q->orWhereHas('entries.finishedProduct', fn ($p) => $p->where('name', 'like', '%'.$search.'%'));
            });
        }

        $reports = $query
            ->orderByDesc('production_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // Eager-load linked PackerReports so the index can show "Update Packing" buttons
        $reports->load('packerReport');

        return view('daily-production.index', compact('reports'));
    }

    public function create()
    {
        $products = FinishedProduct::with('recipes')
            ->orderBy('name')
            ->get();

        $entriesByProduct = collect();

        return view('daily-production.grid', [
            'report' => null,
            'products' => $products,
            'entriesByProduct' => $entriesByProduct,
            'defaultProductionDate' => Carbon::today()->format('Y-m-d'),
        ]);
    }

    public function store(Request $request)
    {
        $payload = $this->validateAndBuildLines($request);
        if ($payload['errors'] !== []) {
            throw ValidationException::withMessages(['sheet' => $payload['errors']]);
        }

        $productionDate = $payload['productionDate'];
        $toSave = $payload['toSave'];
        $products = $payload['products'];

        try {
            DB::beginTransaction();

            $report = DailyProductionReport::create([
                'production_date' => $productionDate,
                'notes' => $request->input('notes'),
                'user_id' => Auth::id(),
            ]);

            $this->persistLines($report, $toSave, $products);
            $newProductIds = array_map('intval', array_keys($toSave));
            if ($newProductIds !== []) {
                $this->productionPackingSyncService->sync($newProductIds);
            }

            // Auto-create a linked PackerReport for this production date
            PackerReport::firstOrCreate(
                ['daily_production_report_id' => $report->id],
                [
                    'pack_date' => $productionDate,
                    'expiration_date' => Carbon::parse($productionDate)->addDays(2)->format('Y-m-d'),
                    'user_id' => Auth::id(),
                    'notes' => null,
                ]
            );

            DB::commit();

            $msg = $toSave === []
                ? 'Daily production report created (no product lines).'
                : 'Daily production saved. Raw materials deducted for '.count($toSave).' product line(s); packed/remaining re-synced.';

            return redirect()
                ->route('daily-production.index')
                ->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Could not save. Please try again.');
        }
    }

    public function sheet(DailyProductionReport $dailyProductionReport)
    {
        $dailyProductionReport->load(['entries.ingredients']);
        $products = FinishedProduct::with('recipes')
            ->orderBy('name')
            ->get();

        $entriesByProduct = $dailyProductionReport->entries->keyBy('finished_product_id');

        return view('daily-production.grid', [
            'report' => $dailyProductionReport,
            'products' => $products,
            'entriesByProduct' => $entriesByProduct,
            'defaultProductionDate' => $dailyProductionReport->production_date->format('Y-m-d'),
        ]);
    }

    public function saveSheet(Request $request, DailyProductionReport $dailyProductionReport)
    {
        $payload = $this->validateAndBuildLines($request);
        if ($payload['errors'] !== []) {
            throw ValidationException::withMessages(['sheet' => $payload['errors']]);
        }

        $productionDate = $payload['productionDate'];
        $toSave = $payload['toSave'];
        $products = $payload['products'];

        try {
            DB::beginTransaction();

            $affectedProductIds = $dailyProductionReport->entries()
                ->pluck('finished_product_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $this->revertReportIngredients($dailyProductionReport);
            $dailyProductionReport->entries()->delete();

            $dailyProductionReport->update([
                'production_date' => $productionDate,
                'notes' => $request->input('notes'),
            ]);

            $this->persistLines($dailyProductionReport, $toSave, $products);
            $affectedProductIds = array_values(array_unique(array_merge($affectedProductIds, array_map('intval', array_keys($toSave)))));
            if ($affectedProductIds !== []) {
                $this->productionPackingSyncService->sync($affectedProductIds);
            }

            // Keep the linked PackerReport's pack_date in sync with production_date
            $linkedPacker = $dailyProductionReport->packerReport;
            if ($linkedPacker) {
                $linkedPacker->update(['pack_date' => $productionDate]);
            } else {
                PackerReport::firstOrCreate(
                    ['daily_production_report_id' => $dailyProductionReport->id],
                    [
                        'pack_date' => $productionDate,
                        'expiration_date' => Carbon::parse($productionDate)->addDays(2)->format('Y-m-d'),
                        'user_id' => Auth::id(),
                        'notes' => null,
                    ]
                );
            }

            DB::commit();

            $msg = $toSave === []
                ? 'Report updated — lines cleared. Raw materials restored.'
                : 'Report updated. Raw materials adjusted for '.count($toSave).' product line(s); packed/remaining re-synced.';

            return redirect()
                ->route('daily-production.index')
                ->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Could not save. Please try again.');
        }
    }

    public function destroy(DailyProductionReport $dailyProductionReport)
    {
        try {
            DB::beginTransaction();
            $affectedProductIds = $dailyProductionReport->entries()
                ->pluck('finished_product_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $this->revertReportIngredients($dailyProductionReport);
            $dailyProductionReport->entries()->delete();
            $dailyProductionReport->delete();
            if ($affectedProductIds !== []) {
                $this->productionPackingSyncService->sync($affectedProductIds);
            }
            DB::commit();

            return redirect()
                ->route('daily-production.index')
                ->with('success', 'Daily production report deleted. Raw materials restored and packed/remaining re-synced.');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return back()->with('error', 'Delete failed. Please try again.');
        }
    }

    /**
     * @return array{errors: list<string>, toSave: array<int, array>, products: \Illuminate\Support\Collection, productionDate: string}
     */
    protected function validateAndBuildLines(Request $request): array
    {
        $request->validate([
            'production_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'lines' => 'required|array',
            'lines.*.number_of_mix' => 'nullable|numeric|min:0|max:999',
            'lines.*.standard_yield' => 'nullable|numeric|min:0|max:9999999',
            'lines.*.actual_yield' => 'nullable|numeric|min:0|max:9999999',
            'lines.*.rejects' => 'nullable|numeric|min:0|max:9999999',
            'lines.*.unfinished' => 'nullable|string|max:500',
        ]);

        $productionDate = Carbon::parse($request->production_date)->format('Y-m-d');
        $lines = $request->input('lines', []);

        $products = FinishedProduct::with('recipes.rawMaterial')
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $errors = [];
        $toSave = [];

        foreach ($products as $pid => $product) {
            $row = $lines[$pid] ?? [];
            $mix = $this->parseNonNegativeDecimal($row['number_of_mix'] ?? null);
            $std = $this->parseNonNegativeDecimal($row['standard_yield'] ?? null);
            $act = $this->parseNonNegativeDecimal($row['actual_yield'] ?? null);
            $rejects = $this->parseNonNegativeDecimal($row['rejects'] ?? null);
            $unfinished = isset($row['unfinished']) ? trim((string) $row['unfinished']) : '';
            $hasNumbers = ($mix > 0) || $std > 0 || $act > 0 || $rejects > 0 || $unfinished !== '';

            if (! $hasNumbers) {
                continue;
            }

            if ($mix <= 0) {
                $errors[] = "{$product->name}: # of mix is required (greater than 0) when entering a row.";

                continue;
            }

            if ($product->recipes->isEmpty()) {
                $errors[] = "{$product->name}: add a recipe before recording production.";

                continue;
            }

            $toSave[$pid] = [
                'number_of_mix' => round($mix, 2),
                'standard_yield' => $std,
                'actual_yield' => $act,
                'rejects' => $rejects,
                'unfinished' => $unfinished !== '' ? $unfinished : null,
            ];
        }

        return [
            'errors' => $errors,
            'toSave' => $toSave,
            'products' => $products,
            'productionDate' => $productionDate,
        ];
    }

    /**
     * Empty or missing numeric fields are treated as 0 (non-negative).
     */
    protected function parseNonNegativeDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (! is_numeric($value)) {
            return 0.0;
        }

        $n = (float) $value;

        return $n < 0 ? 0.0 : $n;
    }

    protected function revertReportIngredients(DailyProductionReport $report): void
    {
        $report->load('entries.ingredients');

        foreach ($report->entries as $entry) {
            foreach ($entry->ingredients as $ing) {
                DB::table('raw_materials')
                    ->where('id', $ing->raw_material_id)
                    ->increment('quantity', $ing->quantity_used);
            }
            $entry->ingredients()->delete();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $toSave
     * @param  \Illuminate\Support\Collection<int, \App\Models\FinishedProduct>  $products
     */
    protected function persistLines(DailyProductionReport $report, array $toSave, $products): void
    {
        foreach ($toSave as $productId => $payload) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $ingredientLines = $this->ingredientLinesFromRecipe($product, $payload['number_of_mix']);

            $entry = DailyProductionEntry::create([
                'daily_production_report_id' => $report->id,
                'finished_product_id' => $productId,
                'number_of_mix' => $payload['number_of_mix'],
                'standard_yield' => $payload['standard_yield'],
                'actual_yield' => $payload['actual_yield'],
                'rejects' => $payload['rejects'],
                'packed_quantity' => 0,
                'unfinished' => $payload['unfinished'],
                'unpacked' => 0,
                'notes' => null,
                'user_id' => Auth::id(),
            ]);

            foreach ($ingredientLines as $line) {
                DailyProductionIngredient::create([
                    'daily_production_entry_id' => $entry->id,
                    'raw_material_id' => $line['raw_material_id'],
                    'quantity_used' => $line['quantity_used'],
                    'input_quantity' => $line['input_quantity'],
                    'input_unit' => $line['input_unit'],
                ]);

                // Apply recipe × mix deduction even when on-hand goes negative (production before stock is fully recorded).
                $material = RawMaterial::query()
                    ->whereKey($line['raw_material_id'])
                    ->lockForUpdate()
                    ->first();

                if ($material) {
                    $use = (float) $line['quantity_used'];
                    $material->quantity = round((float) $material->quantity - $use, 4);
                    $material->save();
                }
            }
        }
    }

    /**
     * @return array<int, array{raw_material_id:int, quantity_used:float, input_quantity:float, input_unit:string}>
     */
    protected function ingredientLinesFromRecipe(FinishedProduct $product, float $numberOfMix): array
    {
        $lines = [];

        foreach ($product->recipes as $recipe) {
            $rm = $recipe->rawMaterial;
            if (! $rm) {
                continue;
            }

            $qtyStorage = round((float) $recipe->quantity_needed * $numberOfMix, 6);
            if ($qtyStorage <= 0) {
                continue;
            }

            $canonical = RawMaterialUnit::resolveToCanonical($rm->unit) ?? strtoupper(trim((string) $rm->unit));

            $lines[] = [
                'raw_material_id' => (int) $recipe->raw_material_id,
                'quantity_used' => $qtyStorage,
                'input_quantity' => $qtyStorage,
                'input_unit' => $canonical,
            ];
        }

        return $lines;
    }
}
