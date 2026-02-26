<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\RawMaterial;
use App\Models\RawMaterialUsage;
use App\Models\FinishedProduct;
use App\Models\FinishedProductRestock;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\ProductAlert;

class ResetDataSeeder extends Seeder
{
    /**
     * Reset all data except users
     */
    public function run(): void
    {
        echo "Resetting database (keeping users)...\n\n";

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Delete in correct order
        echo "Deleting Product Alerts...\n";
        ProductAlert::query()->delete();

        echo "Deleting Sales...\n";
        Sale::query()->delete();

        echo "Deleting Expenses...\n";
        Expense::query()->delete();

        echo "Deleting Finished Product Restocks...\n";
        FinishedProductRestock::query()->delete();

        echo "Deleting Finished Products...\n";
        FinishedProduct::query()->delete();

        echo "Deleting Raw Material Usages...\n";
        RawMaterialUsage::query()->delete();

        echo "Deleting Raw Materials...\n";
        RawMaterial::query()->delete();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        echo "\n========================================\n";
        echo "✓ Database reset successfully!\n";
        echo "========================================\n";
        echo "\n";
        echo "All data deleted except:\n";
        echo "- Users\n";
        echo "- Password Reset Tokens\n";
        echo "\n";
        echo "You can now start fresh or run:\n";
        echo "php artisan db:seed\n";
        echo "to populate with sample data again.\n";
        echo "========================================\n";
    }
}