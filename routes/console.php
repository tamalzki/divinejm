<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sales:fix-legacy-data {--dry-run : Preview changes without saving} {--use-deployed-total : For zero-total DRs, set total from deployed qty x unit price}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $useDeployedTotal = (bool) $this->option('use-deployed-total');

    $updated = 0;
    $scanned = 0;

    Sale::with('items')->chunkById(200, function ($sales) use (&$updated, &$scanned, $dryRun, $useDeployedTotal) {
        foreach ($sales as $sale) {
            $scanned++;

            $original = [
                'total_amount' => (float) $sale->total_amount,
                'amount_paid' => (float) $sale->amount_paid,
                'balance' => (float) $sale->balance,
                'payment_status' => (string) $sale->payment_status,
            ];

            $soldTotal = 0.0;
            $deployedTotal = 0.0;

            foreach ($sale->items as $item) {
                $soldSubtotal = max(0, (float) $item->quantity_sold) * (float) $item->unit_price;
                $deployedSubtotal = max(0, (float) $item->quantity_deployed) * (float) $item->unit_price;
                $soldTotal += $soldSubtotal;
                $deployedTotal += $deployedSubtotal;

                if ((float) $item->subtotal !== $soldSubtotal && ! $dryRun) {
                    $item->subtotal = $soldSubtotal;
                    $item->saveQuietly();
                }
            }

            // Keep canonical total based on sold subtotal unless explicitly requested.
            $newTotal = $soldTotal;
            if ($useDeployedTotal && $newTotal <= 0 && $deployedTotal > 0) {
                $newTotal = $deployedTotal;
            }

            $lessAmount = max(0, (float) ($sale->less_amount ?? 0));
            $effectiveTotal = max(0, $newTotal - $lessAmount);
            $amountPaid = max(0, (float) $sale->amount_paid);
            $newBalance = max(0, $effectiveTotal - $amountPaid);

            // Fix legacy status bug: zero payment should never be "paid".
            if ($amountPaid <= 0) {
                $newStatus = 'to_be_collected';
            } elseif ($newBalance <= 0 && $effectiveTotal > 0) {
                $newStatus = 'paid';
            } else {
                $newStatus = 'partial';
            }

            $hasChanges =
                (float) $sale->total_amount !== $newTotal ||
                (float) $sale->balance !== $newBalance ||
                (string) $sale->payment_status !== $newStatus;

            if ($hasChanges) {
                $updated++;

                if (! $dryRun) {
                    $sale->total_amount = $newTotal;
                    $sale->balance = $newBalance;
                    $sale->payment_status = $newStatus;
                    $sale->saveQuietly();
                }
            }
        }
    });

    $this->newLine();
    $this->info('Legacy sales/DR data scan complete.');
    $this->line('Scanned: '.$scanned);
    $this->line('Updated: '.$updated.($dryRun ? ' (dry run, no writes)' : ''));
    $this->line('Mode: '.($useDeployedTotal ? 'use deployed total fallback' : 'sold subtotal only'));
})->purpose('Fix legacy DR totals, balances, and payment statuses');
