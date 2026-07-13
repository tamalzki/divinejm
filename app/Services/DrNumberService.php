<?php

namespace App\Services;

use App\Models\DrNumberCounter;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class DrNumberService
{
    /**
     * Atomically reserve and return the next DR number (4+ digit, zero-padded).
     * Must run inside a DB transaction — locks the single counter row so
     * concurrent deliveries can never receive the same number, and skips
     * past any value that collides with legacy free-text DR numbers.
     */
    public static function next(): string
    {
        return DB::transaction(function () {
            $counter = DrNumberCounter::lockForUpdate()->first()
                ?? DrNumberCounter::create(['next_number' => 0]);

            do {
                $candidate = self::format($counter->next_number);
                $counter->increment('next_number');
            } while (self::isTaken($candidate));

            return $candidate;
        });
    }

    /**
     * True if a DR number is already in use (by an auto-generated or a
     * manually-encoded delivery). Used to validate manual DR entry, and
     * internally by next() to skip past manually-encoded legacy numbers.
     */
    public static function isTaken(string $drNumber): bool
    {
        return Sale::where('dr_number', $drNumber)->exists()
            || StockMovement::where('reference_number', $drNumber)->exists();
    }

    /**
     * Read-only preview of the next DR number, for display only — the
     * authoritative number is assigned by next() at save time.
     */
    public static function peek(): string
    {
        $counter = DrNumberCounter::first();

        return self::format($counter->next_number ?? 0);
    }

    private static function format(int $number): string
    {
        return str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
