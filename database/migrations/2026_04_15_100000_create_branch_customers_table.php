<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['branch_id', 'sort_order']);
        });

        $this->backfillFromBranchesJson();
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_customers');
    }

    private function backfillFromBranchesJson(): void
    {
        if (! Schema::hasColumn('branches', 'customers')) {
            return;
        }

        $branches = DB::table('branches')->select('id', 'customers')->get();
        $now = now();

        foreach ($branches as $row) {
            $items = $this->decodeCustomersJson($row->customers);
            if ($items === []) {
                continue;
            }

            foreach (array_values($items) as $i => $item) {
                $name = $item['name'] ?? null;
                if ($name === null || trim((string) $name) === '') {
                    continue;
                }

                DB::table('branch_customers')->insert([
                    'branch_id' => $row->id,
                    'name' => trim((string) $name),
                    'phone' => isset($item['phone']) && $item['phone'] !== '' && $item['phone'] !== null
                        ? trim((string) $item['phone'])
                        : null,
                    'email' => null,
                    'address' => null,
                    'notes' => null,
                    'sort_order' => $i,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * @return list<array{name?: string, phone?: string|null}>
     */
    private function decodeCustomersJson(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $item) {
            if (is_string($item)) {
                $out[] = ['name' => $item, 'phone' => null];

                continue;
            }
            if (is_array($item) && isset($item['name'])) {
                $out[] = [
                    'name' => $item['name'],
                    'phone' => $item['phone'] ?? null,
                ];
            }
        }

        return $out;
    }
};
