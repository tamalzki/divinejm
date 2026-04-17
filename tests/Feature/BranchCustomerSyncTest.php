<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchCustomer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BranchCustomerSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('branch_customers');
        Schema::dropIfExists('branches');

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

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
    }

    public function test_sync_customers_from_form_persists_sort_order_in_submission_order(): void
    {
        $branch = Branch::create([
            'name' => 'Test Area',
            'code' => 'TST',
            'address' => null,
            'phone' => null,
            'is_active' => true,
        ]);

        $branch->syncCustomersFromForm([
            ['name' => 'Alpha Store', 'phone' => '111'],
            ['name' => 'Beta Mart', 'phone' => null],
            ['name' => 'Gamma Shop', 'phone' => '333'],
        ]);

        $rows = BranchCustomer::query()
            ->where('branch_id', $branch->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $rows);
        $this->assertSame('Alpha Store', $rows[0]->name);
        $this->assertSame(0, (int) $rows[0]->sort_order);
        $this->assertSame('111', $rows[0]->phone);

        $this->assertSame('Beta Mart', $rows[1]->name);
        $this->assertSame(1, (int) $rows[1]->sort_order);
        $this->assertNull($rows[1]->phone);

        $this->assertSame('Gamma Shop', $rows[2]->name);
        $this->assertSame(2, (int) $rows[2]->sort_order);
        $this->assertSame('333', $rows[2]->phone);
    }

    public function test_sync_customers_from_form_replaces_existing_customers(): void
    {
        $branch = Branch::create([
            'name' => 'Replace Area',
            'code' => 'REP',
            'address' => null,
            'phone' => null,
            'is_active' => true,
        ]);

        $branch->syncCustomersFromForm([
            ['name' => 'Old One', 'phone' => null],
            ['name' => 'Old Two', 'phone' => null],
        ]);

        $this->assertSame(2, BranchCustomer::where('branch_id', $branch->id)->count());

        $branch->syncCustomersFromForm([
            ['name' => 'Only New', 'phone' => '999'],
        ]);

        $rows = BranchCustomer::query()
            ->where('branch_id', $branch->id)
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame('Only New', $rows[0]->name);
        $this->assertSame(0, (int) $rows[0]->sort_order);
        $this->assertSame('999', $rows[0]->phone);
    }

    public function test_sync_customers_from_form_skips_blank_names_and_keeps_index_for_sort_order(): void
    {
        $branch = Branch::create([
            'name' => 'Skip Area',
            'code' => 'SKP',
            'address' => null,
            'phone' => null,
            'is_active' => true,
        ]);

        $branch->syncCustomersFromForm([
            ['name' => '   ', 'phone' => null],
            ['name' => 'Valid', 'phone' => null],
        ]);

        $rows = BranchCustomer::query()
            ->where('branch_id', $branch->id)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame('Valid', $rows[0]->name);
        $this->assertSame(1, (int) $rows[0]->sort_order);
    }

    public function test_add_customer_appends_incrementing_sort_order(): void
    {
        $branch = Branch::create([
            'name' => 'Add Area',
            'code' => 'ADD',
            'address' => null,
            'phone' => null,
            'is_active' => true,
        ]);

        $branch->syncCustomersFromForm([
            ['name' => 'First', 'phone' => null],
            ['name' => 'Second', 'phone' => null],
        ]);

        $branch->addCustomer('Third');

        $rows = BranchCustomer::query()
            ->where('branch_id', $branch->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $rows);
        $this->assertSame('Third', $rows[2]->name);
        $this->assertSame(2, (int) $rows[2]->sort_order);
    }

    public function test_add_customer_does_not_duplicate_same_name(): void
    {
        $branch = Branch::create([
            'name' => 'Dup Area',
            'code' => 'DUP',
            'address' => null,
            'phone' => null,
            'is_active' => true,
        ]);

        $branch->syncCustomersFromForm([
            ['name' => 'Solo', 'phone' => null],
        ]);

        $branch->addCustomer('Solo');

        $this->assertSame(1, BranchCustomer::where('branch_id', $branch->id)->count());
    }

    public function test_branch_customers_are_removed_when_branch_is_deleted(): void
    {
        $branch = Branch::create([
            'name' => 'Cascade Area',
            'code' => 'CAS',
            'address' => null,
            'phone' => null,
            'is_active' => true,
        ]);

        $branch->syncCustomersFromForm([
            ['name' => 'Keep Me', 'phone' => null],
        ]);

        $this->assertSame(1, BranchCustomer::count());

        $branch->delete();

        $this->assertSame(0, BranchCustomer::count());
    }
}
