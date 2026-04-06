<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach ([
            'sale_items', 'sales', 'stock_movements',
            'production_mix_ingredients', 'production_mixes',
            'branch_inventory', 'expenses', 'bank_deposits',
            'finished_products', 'raw_materials', 'branches', 'users',
            'product_alerts', 'finished_product_restocks', 'raw_material_usages',
        ] as $table) {
            try {
                DB::table($table)->truncate();
            } catch (\Exception $e) {
                $this->command->warn("Skipped: {$table}");
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ══════════════════════════════════════════════
        // USERS
        // ══════════════════════════════════════════════

        $this->call(UserSeeder::class);
        $adminId = (int) User::query()->where('email', 'divinejm@admin.com')->value('id');

        DB::table('users')->insert([
            'name' => 'Maria Santos',
            'email' => 'maria@divinejm.com',
            'password' => Hash::make('password'),
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ══════════════════════════════════════════════
        // BRANCHES — real data
        // ══════════════════════════════════════════════

        $branchDefs = [
            [
                'id' => 8,  'name' => 'PANABO',           'code' => 'PAN',
                'customers' => ['JCA', 'JOSMAR', 'AZUENI', 'LAI', 'MAROT ONE STOP SHOP', 'COLE', 'VV VENTURES', 'TRANS EURO'],
            ],
            [
                'id' => 10, 'name' => 'TAGUM',            'code' => 'TAG',
                'customers' => ['STALL #12', 'STALL #20', 'STALL #21', 'STALL #22', 'STALL #23', 'STALL #24', 'STALL #25', 'STALL #5', 'STALL #7', 'STALL #8', 'STALL #31', 'STALL #32', 'STALL #33', 'STALL #35', 'STALL #36', 'STALL #48', 'STALL #50', 'STALL #16', 'STALL #17 & 18', 'STALL #19', 'STALL #5 & 6', 'JOANE', 'NITS', 'OISHI MANJU', 'EVANS', 'LINA', 'LING2', 'ELIJAH', 'JAVEN', 'PRINCESS MELODY', 'VON2', 'IMJS', 'TINKERBELLE', 'GWAPA', 'ANGEL', 'BEBING', 'IA'],
            ],
            [
                'id' => 11, 'name' => 'ECOLAND TERMINAL', 'code' => 'ECO',
                'customers' => ['MAYA', 'TONTON', 'W&A', 'ARTHUR', 'BLESSES', 'LANI', 'PEPE', 'JOCELYN', 'MIDZ', 'KEN', 'BING', 'CHIN2', 'MARILYN', 'MADELYN', 'DODONG'],
            ],
            [
                'id' => 12, 'name' => 'CITY AREA',        'code' => 'CITY',
                'customers' => ['VILLAPARK MINI MART', 'DMMA', 'VENUS', 'BABY AMA', 'BETH', 'ERNA', 'SHELL BAJADA', 'SHELL SASA', 'LUPIN'],
            ],
            [
                'id' => 13, 'name' => 'DIGOS',            'code' => 'DG',
                'customers' => ['LEDOUX COMPANY', 'MARILOR', 'MANCAO', 'GLORIA 1', 'FRAME ROSE', 'GLORIA 2', 'ISAY', 'ROSE', 'EMILY', 'RODRIGO', 'NICE', 'PEARLY', 'LITA PAYAT', 'TISAY', 'MILDRED', 'LIZA', 'NELIA', 'NILDA', 'PRANGELS', 'ROSHELLE MHAY', 'TM STORE'],
            ],
        ];

        $branchIds = [];
        foreach ($branchDefs as $b) {
            $customersJson = json_encode(array_map(fn ($n) => ['name' => $n, 'phone' => null], $b['customers']));
            DB::table('branches')->insert([
                'id' => $b['id'],
                'name' => $b['name'],
                'code' => $b['code'],
                'address' => null,
                'customers' => $customersJson,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $branchIds[$b['code']] = $b['id'];
        }

        $pan = $branchIds['PAN'];
        $tag = $branchIds['TAG'];
        $eco = $branchIds['ECO'];
        $city = $branchIds['CITY'];
        $dg = $branchIds['DG'];
        $allBranchIds = [$pan, $tag, $eco, $city, $dg];

        $customersByBranch = [];
        foreach ($branchDefs as $b) {
            $customersByBranch[$b['id']] = $b['customers'];
        }

        // ══════════════════════════════════════════════
        // RAW MATERIALS — real data
        // ══════════════════════════════════════════════

        $rmDefs = [
            [1,  'WHITE SUGAR - busco',      'KG',    'ingredient', 48.10,    50.00,    64.00],
            [2,  'BROWN SUGAR',              'g',     'ingredient', 50000.00, 50000.00, 0.05],
            [3,  'MARGARINE - SPRING',       'g',     'ingredient', 44650.00, 45000.00, 0.09],
            [4,  'MARGARINE - TOP ONE',      'g',     'ingredient', 45000.00, 45000.00, 0.07],
            [5,  'LARD - LUCKY CHOICE',      'g',     'ingredient', 30000.00, 30000.00, 0.07],
            [6,  'PALM OIL',                 'g',     'ingredient', 17800.00, 18000.00, 0.08],
            [7,  'SALT',                     'g',     'ingredient', 25000.00, 5000.00,  0.01],
            [8,  'VANILLA',                  'g',     'ingredient', 3734.99,  3784.99,  0.03],
            [9,  '1ST CLASS - SAN MIG',      'Kg',    'ingredient', 25.00,    25.00,    39.40],
            [10, '3RD CLASS - ISLA',         'Kg',    'ingredient', 121.50,   25.00,    35.40],
            [11, '3RD CLASS - 77B',          'Kg',    'ingredient', 25.00,    25.00,    31.20],
            [12, 'ANTI-MOLDS SUPER 7',       'g',     'ingredient', 500.00,   1000.00,  0.35],
            [13, 'KINUGAY',                  'g',     'ingredient', 50000.00, 50000.00, 0.07],
            [14, 'GLUCOSE',                  'g',     'ingredient', 1000.00,  1000.00,  0.06],
            [15, 'EGG',                      'PIECE', 'ingredient', 27.00,    60.00,    7.00],
            [16, 'EVAPORATED MILK',          'CAN',   'ingredient', 5.00,     6.00,     35.00],
            [17, 'UBE COLORING - NECO',      'g',     'ingredient', 500.00,   500.00,   0.43],
            [18, 'EGG YELLOW COLORING - NECO', 'g',    'ingredient', 500.00,   500.00,   0.27],
            [19, 'UBE ESSENCE',              'g',     'ingredient', 1000.00,  500.00,   1.80],
            [20, 'DURIAN ESSENCE',           'g',     'ingredient', 1000.00,  499.99,   1.80],
            [21, 'SESAME SEEDS',             'g',     'ingredient', 1500.00,  2000.00,  0.15],
            [22, 'MONGO SEEDS',              'Kg',    'ingredient', 25.00,    25.00,    43.00],
            [23, '4X10 PP02',                'REAM',  'packaging',  2.00,     2.00,     415.00],
            [24, '5X10 PP02',                'REAM',  'packaging',  2.00,     2.00,     450.00],
            [25, 'OPP ROLL PLAIN',           'ROLL',  'packaging',  5.00,     5.00,     230.00],
            [26, 'GLASSINE',                 'REAM',  'packaging',  5.00,     3.00,     850.00],
            [27, 'CARBONATO',                'g',     'ingredient', 985.00,   1000.00,  0.02],
            [28, 'SKIMMILK',                 'g',     'ingredient', 25000.00, 5000.00,  0.06],
            [29, '1ST CLASS - 1A',           'Kg',    'ingredient', 25.00,    100.00,   37.60],
            [30, '3RD CLASS - 3A',           'Kg',    'ingredient', 75.00,    100.00,   36.20],
            [31, '3RD CLASS - ISLAND',       'Kg',    'ingredient', 50.00,    75.00,    36.20],
            [32, 'BAKING POWDER',            'g',     'ingredient', 980.00,   2000.00,  0.07],
            [33, 'LATIK',                    'Kg',    'ingredient', 40.00,    40.00,    42.65],
            [34, 'MONGO FILLING',            'Kg',    'ingredient', 5.00,     5.00,     108.50],
            [35, 'UBE FILLING',              'Kg',    'ingredient', 10.00,    20.00,    120.00],
            [36, '4X8 PPO2',                 'REAM',  'packaging',  2.00,     2.00,     260.00],
            [37, 'WATER',                    'LITERS', 'ingredient', 50.00,    50000.00, 2.40],
            [38, 'CORN OIL',                 'Kg',    'ingredient', 34.00,    34.00,    129.00],
            [41, 'CORNSTARCH',               'Kg',    'ingredient', 25.00,    50.00,    37.20],
            [42, 'LARD - SPRING',            'g',     'ingredient', 40.00,    25.00,    0.12],
            [43, 'YEAST',                    'g',     'ingredient', 500.00,   500.00,   0.26],
            [44, 'DURIAN FILLING',           'G',     'ingredient', 0.00,     50.00,    0.12],
        ];

        $rmIds = [];
        foreach ($rmDefs as [$id, $name, $unit, $cat, $qty, $min, $price]) {
            DB::table('raw_materials')->insert([
                'id' => $id,
                'name' => $name,
                'unit' => $unit,
                'category' => $cat,
                'quantity' => $qty,
                'minimum_stock' => $min,
                'unit_price' => $price,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $rmIds[$name] = $id;
        }

        // ══════════════════════════════════════════════
        // FINISHED PRODUCTS — real data
        // ══════════════════════════════════════════════

        $fpDefs = [
            // id, name, sku, cost_price, selling_price, minimum_stock, shelf_life_days
            [3,  'DICE UBE',       'PROD-1508', 928.27,  22.00, 200.00, 60],
            [4,  'DICE MONGO',     'PROD-5400', 845.48,  22.00, 200.00, 60],
            [5,  'HOPIA MONGO',    'PROD-9163', 422.92,  22.00, 100.00, 60],
            [6,  'HOPIA UBE',      'PROD-7822', 468.89,  22.00, 150.00, 60],
            [7,  'PIAYA PLAIN',    'PROD-5486', 3304.10, 24.00, 499.99, 45],
            [8,  'OTAP PLAIN',     'PROD-6990', 445.45,  22.00, 300.00, 90],
            [9,  'SESAME KISSES',  'PROD-5503', 721.75,  23.00, 300.00, 90],
            [10, 'BUTTER COOKIES', 'PROD-8492', 352.20,  18.00, 50.00,  90],
            [11, 'SAMPAGUITA',     'PROD-1727', 352.20,  18.00, 50.00,  90],
            [12, 'DICE DURIAN',    'PROD-6127', 891.48,  22.00, 100.00, 60],
            [13, 'HOPIA DURIAN',   'PROD-9168', 445.92,  22.00, 100.00, 60],
        ];

        $fpIds = [];
        $fpPrices = [];
        $fpCosts = [];
        $mfgDate = $now->copy()->subDays(15)->format('Y-m-d');

        foreach ($fpDefs as [$id, $name, $sku, $cost, $sell, $min, $shelf]) {
            $onHand = rand(300, 800);
            $out = rand(100, 400);
            DB::table('finished_products')->insert([
                'id' => $id,
                'name' => $name,
                'sku' => $sku,
                'product_type' => 'manufactured',
                'quantity' => $onHand,
                'stock_on_hand' => $onHand,
                'stock_out' => $out,
                'minimum_stock' => $min,
                'cost_price' => $cost,
                'total_cost' => round($cost * $onHand, 2),
                'selling_price' => $sell,
                'manufacturing_date' => $mfgDate,
                'expiry_date' => $now->copy()->addDays($shelf)->format('Y-m-d'),
                'shelf_life_days' => $shelf,
                'is_expired' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $fpIds[$sku] = $id;
            $fpPrices[$id] = $sell;
            $fpCosts[$id] = $cost;
        }

        $allFpIds = array_values($fpIds);

        // ══════════════════════════════════════════════
        // PRODUCTION MIXES — randomized
        // ══════════════════════════════════════════════

        // Ingredient sets per product (realistic)
        $ingredientSets = [
            3 => [[2, 500], [3, 300], [9, 1], [15, 12], [17, 5], [19, 5], [35, 2], [23, 1]],   // DICE UBE
            4 => [[2, 500], [3, 300], [9, 1], [15, 12], [27, 5], [34, 2], [23, 1]],           // DICE MONGO
            5 => [[2, 400], [4, 200], [10, 1], [15, 10], [34, 2], [24, 1]],                 // HOPIA MONGO
            6 => [[2, 400], [4, 200], [10, 1], [15, 10], [17, 5], [19, 5], [35, 2], [24, 1]],   // HOPIA UBE
            7 => [[2, 800], [5, 400], [11, 2], [13, 300], [7, 50], [25, 2]],                // PIAYA PLAIN
            8 => [[2, 300], [3, 200], [9, 1], [15, 8], [21, 100], [26, 1]],                 // OTAP PLAIN
            9 => [[2, 300], [3, 150], [9, 1], [15, 8], [21, 200], [23, 1]],                 // SESAME KISSES
            10 => [[2, 250], [3, 200], [9, 1], [15, 6], [8, 10], [32, 5], [36, 1]],            // BUTTER COOKIES
            11 => [[2, 250], [3, 200], [9, 1], [15, 6], [8, 10], [32, 5], [36, 1]],            // SAMPAGUITA
            12 => [[2, 500], [3, 300], [9, 1], [15, 12], [20, 5], [44, 2], [23, 1]],           // DICE DURIAN
            13 => [[2, 400], [4, 200], [10, 1], [15, 10], [20, 5], [44, 2], [24, 1]],          // HOPIA DURIAN
        ];

        $mixIds = [];
        $batchNumbers = [];
        $mixConfigs = [];

        // Generate 2 batches per product spread over last 45 days
        $batchCounter = 1;
        foreach ($allFpIds as $fpId) {
            foreach ([rand(30, 44), rand(5, 15)] as $daysAgo) {
                $mixDate = $now->copy()->subDays($daysAgo)->format('Y-m-d');
                $shelf = collect($fpDefs)->firstWhere(0, $fpId)[6] ?? 60;
                $expiry = $now->copy()->subDays($daysAgo)->addDays($shelf)->format('Y-m-d');
                $actual = rand(200, 500);
                $rejected = rand(0, 10);
                $batchNo = 'BATCH-'.str_pad($batchCounter, 4, '0', STR_PAD_LEFT);

                $idx = count($mixIds);
                $mixConfigs[$idx] = [$fpId, $actual, $rejected, 1, $shelf, $daysAgo];
                $batchNumbers[$idx] = $batchNo;

                $mixId = DB::table('production_mixes')->insertGetId([
                    'finished_product_id' => $fpId,
                    'batch_number' => $batchNo,
                    'mix_date' => $mixDate,
                    'expected_output' => $actual + rand(5, 20),
                    'actual_output' => $actual,
                    'rejected_quantity' => $rejected,
                    'expiration_date' => $expiry,
                    'multiplier' => 1,
                    'status' => 'completed',
                    'notes' => "Batch {$batchNo} produced on {$mixDate}.",
                    'user_id' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $mixIds[$idx] = $mixId;

                // Ingredients
                foreach ($ingredientSets[$fpId] as [$rmId, $qty]) {
                    DB::table('production_mix_ingredients')->insert([
                        'production_mix_id' => $mixId,
                        'raw_material_id' => $rmId,
                        'quantity_used' => $qty,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                // Stock movement for production
                DB::table('stock_movements')->insert([
                    'finished_product_id' => $fpId,
                    'branch_id' => null,
                    'production_mix_id' => $mixId,
                    'movement_type' => 'production',
                    'quantity' => $actual,
                    'movement_date' => $mixDate,
                    'batch_number' => $batchNo,
                    'expiration_date' => $expiry,
                    'notes' => "Produced from {$batchNo}",
                    'user_id' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $batchCounter++;
            }
        }

        $mixCount = count($mixIds);

        // ══════════════════════════════════════════════
        // BRANCH INVENTORY — one delivery per branch per product
        // ══════════════════════════════════════════════

        $deliveryDrCounter = 5001;

        foreach ($allBranchIds as $branchId) {
            $custList = $customersByBranch[$branchId];

            foreach ($allFpIds as $fpId) {
                $qty = rand(30, 120);
                $customer = $custList[array_rand($custList)];
                $mixIdx = array_rand($mixIds);
                $batchNo = $batchNumbers[$mixIdx];
                $cfg = $mixConfigs[$mixIdx];
                $expiry = $now->copy()->subDays($cfg[5])->addDays($cfg[4])->format('Y-m-d');
                $drNumber = 'DR-'.$deliveryDrCounter++;
                $delDate = $now->copy()->subDays(rand(5, 20))->format('Y-m-d');

                DB::table('branch_inventory')->insert([
                    'branch_id' => $branchId,
                    'finished_product_id' => $fpId,
                    'quantity' => $qty,
                    'batch_number' => $batchNo,
                    'expiration_date' => $expiry,
                    'batch_expiry_date' => $expiry,
                    'is_expired' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('stock_movements')->insert([
                    'finished_product_id' => $fpId,
                    'branch_id' => $branchId,
                    'production_mix_id' => null,
                    'movement_type' => 'transfer_out',
                    'quantity' => $qty,
                    'movement_date' => $delDate,
                    'batch_number' => $batchNo,
                    'expiration_date' => $expiry,
                    'reference_number' => $drNumber,
                    'notes' => 'Customer: '.$customer,
                    'user_id' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // ══════════════════════════════════════════════
        // SALES — randomized, using real customers & products
        // ══════════════════════════════════════════════

        $drCounter = 1001;

        for ($daysAgo = 44; $daysAgo >= 0; $daysAgo--) {
            $saleDate = $now->copy()->subDays($daysAgo)->format('Y-m-d');
            $salesPerDay = rand(4, 8);

            for ($s = 0; $s < $salesPerDay; $s++) {
                $branchId = $allBranchIds[array_rand($allBranchIds)];
                $custList = $customersByBranch[$branchId];
                $customer = $custList[array_rand($custList)];
                $drNumber = 'DR-'.$drCounter++;
                $itemCount = rand(2, 5);

                $shuffled = $allFpIds;
                shuffle($shuffled);
                $pickedFps = array_slice($shuffled, 0, $itemCount);

                $totalAmount = 0;
                $saleItemsData = [];

                foreach ($pickedFps as $fpId) {
                    $qtySold = rand(10, 50);
                    $price = $fpPrices[$fpId];
                    $subtotal = $qtySold * $price;
                    $totalAmount += $subtotal;
                    $mixIdx = array_rand($batchNumbers);

                    $saleItemsData[] = [
                        'finished_product_id' => $fpId,
                        'quantity_deployed' => $qtySold + rand(0, 10),
                        'quantity_sold' => $qtySold,
                        'quantity_unsold' => rand(0, 5),
                        'quantity_bo' => rand(0, 3),
                        'quantity_replaced' => 0,
                        'unit_price' => $price,
                        'discount' => 0,
                        'subtotal' => $subtotal,
                        'batch_number' => $batchNumbers[$mixIdx],
                    ];
                }

                // Payment logic — older = more likely collected
                $rand = rand(1, 100);
                if ($daysAgo > 14) {
                    if ($rand <= 75) {
                        $status = 'paid';
                        $amtPaid = $totalAmount;
                        $payMode = ['cash', 'gcash', 'cash', 'cash'][rand(0, 3)];
                    } elseif ($rand <= 90) {
                        $status = 'partial';
                        $amtPaid = round($totalAmount * (rand(40, 80) / 100), 2);
                        $payMode = 'cash';
                    } else {
                        $status = 'to_be_collected';
                        $amtPaid = 0;
                        $payMode = null;
                    }
                } else {
                    if ($rand <= 50) {
                        $status = 'paid';
                        $amtPaid = $totalAmount;
                        $payMode = ['cash', 'gcash', 'cash'][rand(0, 2)];
                    } elseif ($rand <= 75) {
                        $status = 'to_be_collected';
                        $amtPaid = 0;
                        $payMode = null;
                    } else {
                        $status = 'partial';
                        $amtPaid = round($totalAmount * (rand(30, 70) / 100), 2);
                        $payMode = 'cash';
                    }
                }

                $payRef = ($payMode === 'gcash') ? '09'.rand(100000000, 999999999) : null;
                $balance = $totalAmount - $amtPaid;
                $payDate = ($status === 'paid') ? $now->copy()->subDays($daysAgo)->addDays(rand(0, 2))->format('Y-m-d') : null;
                $createdAt = $now->copy()->subDays($daysAgo);

                $saleId = DB::table('sales')->insertGetId([
                    'branch_id' => $branchId,
                    'customer_name' => $customer,
                    'dr_number' => $drNumber,
                    'sale_date' => $saleDate,
                    'payment_period' => 'one_time',
                    'due_date' => $now->copy()->subDays($daysAgo)->addDays(7)->format('Y-m-d'),
                    'total_amount' => $totalAmount,
                    'amount_paid' => $amtPaid,
                    'balance' => $balance,
                    'payment_status' => $status,
                    'payment_mode' => $payMode,
                    'payment_method' => $payMode ?? 'cash',
                    'payment_reference' => $payRef,
                    'payment_date' => $payDate,
                    'notes' => null,
                    'status' => 'active',
                    'user_id' => $adminId,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                foreach ($saleItemsData as $item) {
                    DB::table('sale_items')->insert(array_merge(
                        ['sale_id' => $saleId, 'created_at' => $now, 'updated_at' => $now],
                        $item
                    ));
                }
            }
        }

        // ══════════════════════════════════════════════
        // EXPENSES — randomized
        // ══════════════════════════════════════════════

        $expenseRows = [
            ['Utilities',   'Electricity Bill',          4800.00,  2,  'cash'],
            ['Utilities',   'Water Bill',                 650.00,   2,  'cash'],
            ['Transport',   'Delivery Fuel — PANABO',    1200.00,  5,  'cash'],
            ['Transport',   'Delivery Fuel — TAGUM',     1800.00,  5,  'cash'],
            ['Transport',   'Vehicle Maintenance',        2500.00,  12, 'cash'],
            ['Supplies',    'Packaging Materials',        3200.00,  8,  'bank_transfer'],
            ['Supplies',    'Cleaning Supplies',           450.00,  10, 'cash'],
            ['Labor',       'Production Staff Wages',    18000.00,  1,  'bank_transfer'],
            ['Labor',       'Delivery Personnel',         8000.00,  1,  'bank_transfer'],
            ['Marketing',   'Tarpaulin / Signage',        1500.00,  20, 'cash'],
            ['Maintenance', 'Oven Repair',                3500.00,  15, 'cash'],
            ['Utilities',   'Electricity Bill',           4650.00,  32, 'cash'],
            ['Utilities',   'Water Bill',                  620.00,  32, 'cash'],
            ['Labor',       'Production Staff Wages',    18000.00,  31, 'bank_transfer'],
            ['Transport',   'Delivery Fuel — All Areas',  3200.00,  33, 'cash'],
            ['Supplies',    'Packaging Materials',        2800.00,  35, 'bank_transfer'],
            ['Labor',       'Delivery Personnel',         8000.00,  31, 'bank_transfer'],
        ];

        foreach ($expenseRows as [$cat, $desc, $amount, $daysAgo, $method]) {
            DB::table('expenses')->insert([
                'category' => $cat,
                'description' => $desc,
                'amount' => $amount,
                'expense_date' => $now->copy()->subDays($daysAgo)->format('Y-m-d'),
                'payment_method' => $method,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ══════════════════════════════════════════════
        // BANK DEPOSITS — randomized
        // ══════════════════════════════════════════════

        $depositRows = [
            ['BDO Unibank', '1234-5678-901', 25000.00, 3],
            ['BDO Unibank', '1234-5678-901', 18500.00, 10],
            ['Metrobank',   '098-765-4321',  32000.00, 7],
            ['BDO Unibank', '1234-5678-901', 20000.00, 17],
            ['Metrobank',   '098-765-4321',  15000.00, 24],
            ['BDO Unibank', '1234-5678-901', 28000.00, 31],
            ['Landbank',    '5555-6666-777', 10000.00, 38],
        ];

        foreach ($depositRows as [$bank, $acct, $amount, $daysAgo]) {
            DB::table('bank_deposits')->insert([
                'bank_name' => $bank,
                'account_number' => $acct,
                'amount' => $amount,
                'deposit_date' => $now->copy()->subDays($daysAgo)->format('Y-m-d'),
                'notes' => null,
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ══════════════════════════════════════════════
        // DONE
        // ══════════════════════════════════════════════

        $this->command->info('');
        $this->command->info('✅  Divine JM Foods — Seeded Successfully!');
        $this->command->info('');
        $this->command->info('  👤  Users            : '.DB::table('users')->count().'   → divinejm@admin.com / divinejmadmin');
        $this->command->info('  🏪  Branches         : '.DB::table('branches')->count());
        $this->command->info('  🌾  Raw Materials    : '.DB::table('raw_materials')->count());
        $this->command->info('  📦  Finished Products: '.DB::table('finished_products')->count());
        $this->command->info('  ⚙️   Production Mixes : '.DB::table('production_mixes')->count());
        $this->command->info('  🚚  Deliveries       : '.DB::table('branch_inventory')->count());
        $this->command->info('  🚚  Sales (DRs)      : '.DB::table('sales')->count());
        $this->command->info('  🧾  Sale Items       : '.DB::table('sale_items')->count());
        $this->command->info('  💸  Expenses         : '.DB::table('expenses')->count());
        $this->command->info('  🏦  Bank Deposits    : '.DB::table('bank_deposits')->count());
        $this->command->info('  📊  Stock Movements  : '.DB::table('stock_movements')->count());
        $this->command->info('');
    }
}
