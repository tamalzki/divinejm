<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RawMaterial;
use App\Models\RawMaterialUsage;
use App\Models\FinishedProduct;
use App\Models\FinishedProductRestock;
use App\Models\Sale;
use App\Models\Expense;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@chipsinventory.com',
            'password' => Hash::make('password'),
        ]);

        echo "✓ Admin user created (admin@chipsinventory.com / password)\n";

        // Create Raw Materials
        $rawMaterials = [
            [
                'name' => 'Cassava Flour',
                'unit' => 'kg',
                'quantity' => 150,
                'minimum_stock' => 50,
                'unit_price' => 45.00,
                'description' => 'High-quality cassava flour for chip production'
            ],
            [
                'name' => 'Corn Flour',
                'unit' => 'kg',
                'quantity' => 200,
                'minimum_stock' => 60,
                'unit_price' => 35.00,
                'description' => 'Premium corn flour'
            ],
            [
                'name' => 'Salt',
                'unit' => 'kg',
                'quantity' => 80,
                'minimum_stock' => 20,
                'unit_price' => 15.00,
                'description' => 'Iodized salt for seasoning'
            ],
            [
                'name' => 'Cheese Powder',
                'unit' => 'kg',
                'quantity' => 25,
                'minimum_stock' => 10,
                'unit_price' => 180.00,
                'description' => 'Cheddar cheese flavoring powder'
            ],
            [
                'name' => 'BBQ Seasoning',
                'unit' => 'kg',
                'quantity' => 30,
                'minimum_stock' => 10,
                'unit_price' => 150.00,
                'description' => 'BBQ flavor seasoning mix'
            ],
            [
                'name' => 'Vegetable Oil',
                'unit' => 'liters',
                'quantity' => 100,
                'minimum_stock' => 30,
                'unit_price' => 65.00,
                'description' => 'Cooking oil for frying'
            ],
            [
                'name' => 'Packaging Bags (Small)',
                'unit' => 'pcs',
                'quantity' => 500,
                'minimum_stock' => 200,
                'unit_price' => 0.50,
                'description' => '50g packaging bags'
            ],
            [
                'name' => 'Packaging Bags (Large)',
                'unit' => 'pcs',
                'quantity' => 300,
                'minimum_stock' => 100,
                'unit_price' => 0.75,
                'description' => '100g packaging bags'
            ],
            [
                'name' => 'Garlic Powder',
                'unit' => 'kg',
                'quantity' => 15,
                'minimum_stock' => 5,
                'unit_price' => 120.00,
                'description' => 'Garlic seasoning'
            ],
            [
                'name' => 'Onion Powder',
                'unit' => 'kg',
                'quantity' => 12,
                'minimum_stock' => 5,
                'unit_price' => 110.00,
                'description' => 'Onion seasoning'
            ],
        ];

        foreach ($rawMaterials as $material) {
            RawMaterial::create($material);
        }

        echo "✓ Raw materials created (10 items)\n";

        // Add some raw material usage history
        $cassavaFlour = RawMaterial::where('name', 'Cassava Flour')->first();
        $cheesePowder = RawMaterial::where('name', 'Cheese Powder')->first();

        RawMaterialUsage::create([
            'raw_material_id' => $cassavaFlour->id,
            'quantity_used' => 50,
            'purpose' => 'production',
            'notes' => 'Production batch for cassava chips',
            'usage_date' => Carbon::now()->subDays(5),
            'user_id' => $admin->id,
        ]);

        RawMaterialUsage::create([
            'raw_material_id' => $cheesePowder->id,
            'quantity_used' => 5,
            'purpose' => 'production',
            'notes' => 'Cheese flavoring for chips',
            'usage_date' => Carbon::now()->subDays(5),
            'user_id' => $admin->id,
        ]);

        // Restock history
        RawMaterialUsage::create([
            'raw_material_id' => $cassavaFlour->id,
            'quantity_used' => -100, // Negative for restock
            'purpose' => 'restock',
            'notes' => 'Restocked from ABC Supplier. Total cost: ₱4,500.00',
            'usage_date' => Carbon::now()->subDays(10),
            'user_id' => $admin->id,
        ]);

        echo "✓ Raw material usage history created\n";

        // Create Finished Products
        $finishedProducts = [
            [
                'name' => 'Cassava Chips Original 50g',
                'sku' => 'PROD-00001',
                'quantity' => 150,
                'minimum_stock' => 50,
                'cost_price' => 12.00,
                'selling_price' => 20.00,
                'description' => 'Original flavor cassava chips, 50g pack'
            ],
            [
                'name' => 'Cassava Chips Cheese 50g',
                'sku' => 'PROD-00002',
                'quantity' => 120,
                'minimum_stock' => 40,
                'cost_price' => 15.00,
                'selling_price' => 25.00,
                'description' => 'Cheese flavor cassava chips, 50g pack'
            ],
            [
                'name' => 'Cassava Chips BBQ 50g',
                'sku' => 'PROD-00003',
                'quantity' => 100,
                'minimum_stock' => 40,
                'cost_price' => 15.00,
                'selling_price' => 25.00,
                'description' => 'BBQ flavor cassava chips, 50g pack'
            ],
            [
                'name' => 'Cassava Chips Original 100g',
                'sku' => 'PROD-00004',
                'quantity' => 80,
                'minimum_stock' => 30,
                'cost_price' => 22.00,
                'selling_price' => 35.00,
                'description' => 'Original flavor cassava chips, 100g pack'
            ],
            [
                'name' => 'Cassava Chips Cheese 100g',
                'sku' => 'PROD-00005',
                'quantity' => 70,
                'minimum_stock' => 30,
                'cost_price' => 28.00,
                'selling_price' => 45.00,
                'description' => 'Cheese flavor cassava chips, 100g pack'
            ],
            [
                'name' => 'Corn Chips Garlic 50g',
                'sku' => 'PROD-00006',
                'quantity' => 90,
                'minimum_stock' => 35,
                'cost_price' => 13.00,
                'selling_price' => 22.00,
                'description' => 'Garlic flavor corn chips, 50g pack'
            ],
            [
                'name' => 'Corn Chips BBQ 100g',
                'sku' => 'PROD-00007',
                'quantity' => 45,
                'minimum_stock' => 30,
                'cost_price' => 24.00,
                'selling_price' => 40.00,
                'description' => 'BBQ flavor corn chips, 100g pack'
            ],
            [
                'name' => 'Cassava Chips Spicy 50g',
                'sku' => 'PROD-00008',
                'quantity' => 20, // Low stock
                'minimum_stock' => 40,
                'cost_price' => 15.00,
                'selling_price' => 25.00,
                'description' => 'Spicy flavor cassava chips, 50g pack'
            ],
        ];

        foreach ($finishedProducts as $product) {
            FinishedProduct::create($product);
        }

        echo "✓ Finished products created (8 items)\n";

        // Add restock history for finished products
        $products = FinishedProduct::all();
        
        foreach ($products->take(5) as $product) {
            FinishedProductRestock::create([
                'finished_product_id' => $product->id,
                'quantity_added' => 100,
                'batch_number' => 'BATCH-2026-' . str_pad($product->id, 3, '0', STR_PAD_LEFT),
                'production_date' => Carbon::now()->subDays(rand(7, 15)),
                'expiry_date' => Carbon::now()->addMonths(6),
                'notes' => 'Initial production batch',
                'user_id' => $admin->id,
            ]);
        }

        echo "✓ Finished product restock history created\n";

        // Create Sales
        $salesData = [
            ['product_idx' => 0, 'qty' => 10, 'days_ago' => 1, 'customer' => 'Sari-Sari Store - Mabini', 'payment' => 'cash'],
            ['product_idx' => 1, 'qty' => 15, 'days_ago' => 1, 'customer' => 'Mini Mart Express', 'payment' => 'card'],
            ['product_idx' => 2, 'qty' => 8, 'days_ago' => 2, 'customer' => 'Corner Store', 'payment' => 'cash'],
            ['product_idx' => 3, 'qty' => 5, 'days_ago' => 2, 'customer' => null, 'payment' => 'cash'],
            ['product_idx' => 4, 'qty' => 12, 'days_ago' => 3, 'customer' => 'ABC Convenience Store', 'payment' => 'bank_transfer'],
            ['product_idx' => 0, 'qty' => 20, 'days_ago' => 3, 'customer' => 'Grocery Depot', 'payment' => 'cash'],
            ['product_idx' => 5, 'qty' => 7, 'days_ago' => 4, 'customer' => null, 'payment' => 'cash'],
            ['product_idx' => 1, 'qty' => 18, 'days_ago' => 5, 'customer' => 'Super Store', 'payment' => 'card'],
            ['product_idx' => 6, 'qty' => 10, 'days_ago' => 5, 'customer' => 'Mini Mart', 'payment' => 'cash'],
            ['product_idx' => 2, 'qty' => 25, 'days_ago' => 6, 'customer' => 'Wholesaler Inc', 'payment' => 'bank_transfer'],
            ['product_idx' => 3, 'qty' => 8, 'days_ago' => 7, 'customer' => 'Retail Shop', 'payment' => 'cash'],
            ['product_idx' => 0, 'qty' => 30, 'days_ago' => 10, 'customer' => 'Big Buyer Co.', 'payment' => 'bank_transfer'],
            ['product_idx' => 4, 'qty' => 6, 'days_ago' => 12, 'customer' => null, 'payment' => 'cash'],
            ['product_idx' => 1, 'qty' => 22, 'days_ago' => 14, 'customer' => 'Family Mart', 'payment' => 'card'],
            ['product_idx' => 5, 'qty' => 15, 'days_ago' => 15, 'customer' => 'Quick Stop', 'payment' => 'cash'],
        ];

        $invoiceCounter = 1;
        foreach ($salesData as $saleData) {
            $product = $products[$saleData['product_idx']];
            
            Sale::create([
                'invoice_number' => 'INV-' . strtoupper(substr(md5($invoiceCounter), 0, 8)),
                'finished_product_id' => $product->id,
                'quantity' => $saleData['qty'],
                'unit_price' => $product->selling_price,
                'total_amount' => $saleData['qty'] * $product->selling_price,
                'customer_name' => $saleData['customer'],
                'payment_method' => $saleData['payment'],
                'sale_date' => Carbon::now()->subDays($saleData['days_ago']),
                'notes' => $saleData['customer'] ? null : 'Walk-in customer',
            ]);
            
            $invoiceCounter++;
        }

        echo "✓ Sales records created (15 sales)\n";

        // Create Expenses
        $expenses = [
            [
                'category' => 'raw_materials',
                'description' => 'Purchase of cassava flour - 50kg',
                'amount' => 2250.00,
                'expense_date' => Carbon::now()->subDays(10),
                'payment_method' => 'cash',
                'notes' => 'From ABC Supplier'
            ],
            [
                'category' => 'utilities',
                'description' => 'Electricity bill - January',
                'amount' => 3500.00,
                'expense_date' => Carbon::now()->subDays(15),
                'payment_method' => 'bank_transfer',
                'notes' => 'Meralco payment'
            ],
            [
                'category' => 'utilities',
                'description' => 'Water bill - January',
                'amount' => 800.00,
                'expense_date' => Carbon::now()->subDays(15),
                'payment_method' => 'bank_transfer',
                'notes' => 'Manila Water'
            ],
            [
                'category' => 'salary',
                'description' => 'Worker salary - January',
                'amount' => 15000.00,
                'expense_date' => Carbon::now()->subDays(5),
                'payment_method' => 'cash',
                'notes' => '3 workers @ 5,000 each'
            ],
            [
                'category' => 'transportation',
                'description' => 'Delivery expenses',
                'amount' => 1200.00,
                'expense_date' => Carbon::now()->subDays(3),
                'payment_method' => 'cash',
                'notes' => 'Product delivery to stores'
            ],
            [
                'category' => 'raw_materials',
                'description' => 'Cheese powder and seasonings',
                'amount' => 4500.00,
                'expense_date' => Carbon::now()->subDays(8),
                'payment_method' => 'card',
                'notes' => 'Bulk purchase'
            ],
            [
                'category' => 'maintenance',
                'description' => 'Equipment maintenance',
                'amount' => 2500.00,
                'expense_date' => Carbon::now()->subDays(12),
                'payment_method' => 'cash',
                'notes' => 'Frying machine repair'
            ],
            [
                'category' => 'raw_materials',
                'description' => 'Packaging materials',
                'amount' => 1800.00,
                'expense_date' => Carbon::now()->subDays(6),
                'payment_method' => 'cash',
                'notes' => 'Bags and labels'
            ],
            [
                'category' => 'rent',
                'description' => 'Factory rent - January',
                'amount' => 12000.00,
                'expense_date' => Carbon::now()->subDays(20),
                'payment_method' => 'bank_transfer',
                'notes' => 'Monthly rental payment'
            ],
            [
                'category' => 'marketing',
                'description' => 'Social media ads',
                'amount' => 1500.00,
                'expense_date' => Carbon::now()->subDays(7),
                'payment_method' => 'card',
                'notes' => 'Facebook and Instagram promotion'
            ],
        ];

        foreach ($expenses as $expense) {
            Expense::create($expense);
        }

        echo "✓ Expenses created (10 records)\n";

        echo "\n";
        echo "========================================\n";
        echo "✓ Database seeded successfully!\n";
        echo "========================================\n";
        echo "\n";
        echo "Login Credentials:\n";
        echo "Email: admin@chipsinventory.com\n";
        echo "Password: password\n";
        echo "\n";
        echo "Summary:\n";
        echo "- 1 Admin User\n";
        echo "- 10 Raw Materials\n";
        echo "- 8 Finished Products (1 with low stock)\n";
        echo "- 15 Sales Records\n";
        echo "- 10 Expense Records\n";
        echo "- Transaction History populated\n";
        echo "========================================\n";
    }
}