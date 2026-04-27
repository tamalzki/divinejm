<?php

namespace App\Http\Controllers;

use App\Models\DailyProductionEntry;
use App\Models\FinishedProduct;
use App\Models\PackerPack;
use App\Models\PackerReport;
use App\Models\PackerSessionLog;
use App\Services\ProductionPackingSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PackerReportController extends Controller
{
    public function __construct(protected ProductionPackingSyncService $productionPackingSyncService) {}

    public function index(Request $request)
    {
        $query = PackerReport::query()
            ->select('packer_reports.*')
            ->selectSub(
                PackerPack::selectRaw('COALESCE(SUM(quantity), 0)')
                    ->whereColumn('packer_report_id', 'packer_reports.id'),
                'total_packs_sum'
            )
            ->selectSub(
                PackerPack::selectRaw('COUNT(DISTINCT finished_product_id)')
                    ->whereColumn('packer_report_id', 'packer_reports.id'),
                'products_count'
            );

        if ($request->filled('from')) {
            $query->whereDate('pack_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('pack_date', '<=', $request->input('to'));
        }

        if (($search = trim((string) $request->input('search', ''))) !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', '%'.$search.'%');
                if (ctype_digit($search)) {
                    $q->orWhere('packer_reports.id', (int) $search);
                }
                $q->orWhereHas('packs.finishedProduct', fn ($p) => $p->where('name', 'like', '%'.$search.'%'));
            });
        }

        $reports = $query
            ->orderByDesc('pack_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $reports->load('dailyProductionReport');

        return view('packer-packs.index', compact('reports'));
    }

    public function create()
    {
        $packerNames = $this->packerColumnNames(null);
        if ($packerNames === []) {
            return redirect()
                ->route('packer-packs.index')
                ->with('error', 'Add at least one packer name in config/packers.php before creating a report.');
        }

        $products = FinishedProduct::orderBy('name')->get();

        $matrix = [];
        foreach ($products as $p) {
            $matrix[$p->id] = array_fill_keys($packerNames, '');
        }

        $defaultPack = Carbon::today()->format('Y-m-d');
        $defaultExpiration = Carbon::today()->addDays(2)->format('Y-m-d');

        return view('packer-packs.grid', [
            'report' => null,
            'products' => $products,
            'packerNames' => $packerNames,
            'matrix' => $matrix,
            'remainingByProduct' => $this->overallRemainingByProduct($products),
            'defaultPackDate' => $defaultPack,
            'defaultExpirationDate' => $defaultExpiration,
        ]);
    }

    public function store(Request $request)
    {
        $packerNames = $this->packerColumnNames(null);
        if ($packerNames === []) {
            return back()
                ->withInput()
                ->with('error', 'No packer names configured in config/packers.php.');
        }

        $request->validate([
            'pack_date' => 'required|date',
            'expiration_date' => 'required|date|after_or_equal:pack_date',
            'notes' => 'nullable|string|max:500',
            'cells' => 'required|array',
            'cells.*' => 'array',
            'cells.*.*' => 'nullable|numeric|min:0|max:999999',
        ], [
            'expiration_date.required' => 'Expiration date is required.',
            'expiration_date.after_or_equal' => 'Expiration must be on or after the pack date.',
        ]);

        $packDate = Carbon::parse($request->pack_date)->format('Y-m-d');
        $expirationDate = Carbon::parse($request->expiration_date)->format('Y-m-d');
        $cells = $request->input('cells', []);
        $products = FinishedProduct::orderBy('name')->get()->keyBy('id');

        $normalized = $this->normalizeCellQuantities($cells, $products, $packerNames);

        try {
            DB::beginTransaction();

            $report = PackerReport::create([
                'pack_date' => $packDate,
                'expiration_date' => $expirationDate,
                'notes' => $request->input('notes'),
                'user_id' => Auth::id(),
            ]);

            $this->applyNormalizedPacks($report, $normalized);
            $affectedProductIds = array_values(array_unique(array_map(
                fn ($row) => (int) $row['finished_product_id'],
                $normalized
            )));
            if ($affectedProductIds !== []) {
                $this->productionPackingSyncService->sync($affectedProductIds);
            }

            DB::commit();

            $msg = 'Packers report created.'.(count($normalized) > 0 ? ' '.count($normalized).' cell(s); inventory and daily production balances updated.' : '');

            return redirect()
                ->route('packer-packs.index')
                ->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Could not save the packers report.');
        }
    }

    public function sheet(PackerReport $packerReport)
    {
        $packerNames = $this->packerColumnNames($packerReport);
        if ($packerNames === []) {
            return redirect()
                ->route('packer-packs.index')
                ->with('error', 'No packer columns available. Add config/packers.php names or restore packer data.');
        }

        // Production-linked mode: show the combined daily production + packer grid
        if ($packerReport->daily_production_report_id) {
            return $this->productionLinkedSheet($packerReport, $packerNames);
        }

        $products = FinishedProduct::orderBy('name')->get();

        $packs = $packerReport->packs()->get();
        $matrix = [];
        foreach ($products as $p) {
            $matrix[$p->id] = array_fill_keys($packerNames, '');
        }
        foreach ($packs as $pack) {
            if (! isset($matrix[$pack->finished_product_id][$pack->packer_name])) {
                continue;
            }
            $matrix[$pack->finished_product_id][$pack->packer_name] = (string) (int) $pack->quantity;
        }

        return view('packer-packs.grid', [
            'report' => $packerReport,
            'products' => $products,
            'packerNames' => $packerNames,
            'matrix' => $matrix,
            'remainingByProduct' => $this->overallRemainingByProduct($products),
            'defaultPackDate' => $packerReport->pack_date->format('Y-m-d'),
            'defaultExpirationDate' => $packerReport->expiration_date?->format('Y-m-d') ?? $packerReport->pack_date->copy()->addDays(2)->format('Y-m-d'),
        ]);
    }

    protected function productionLinkedSheet(PackerReport $packerReport, array $packerNames): \Illuminate\View\View
    {
        $dailyReport = $packerReport->dailyProductionReport()->with('entries.finishedProduct')->first();

        // Build entries keyed by finished_product_id
        $entriesByProduct = collect();
        if ($dailyReport) {
            $entriesByProduct = $dailyReport->entries->keyBy('finished_product_id');
        }

        // Products in THIS production only (ordered by name)
        $productIds = $entriesByProduct->keys()->all();
        $products = FinishedProduct::whereIn('id', $productIds)->orderBy('name')->get();

        // Matrix is always empty (zeros) — user enters NEW incremental quantities each session
        $matrix = [];
        foreach ($products as $p) {
            $matrix[$p->id] = array_fill_keys($packerNames, '');
        }

        // Session history for this report
        $sessionLogs = $packerReport->sessionLogs()->with('savedBy')->get();

        return view('packer-packs.production-grid', [
            'report'           => $packerReport,
            'dailyReport'      => $dailyReport,
            'products'         => $products,
            'entriesByProduct' => $entriesByProduct,
            'packerNames'      => $packerNames,
            'matrix'           => $matrix,
            'sessionLogs'      => $sessionLogs,
            'defaultPackDate'  => $packerReport->pack_date->format('Y-m-d'),
            'defaultExpirationDate' => $packerReport->expiration_date?->format('Y-m-d') ?? $packerReport->pack_date->copy()->addDays(2)->format('Y-m-d'),
        ]);
    }

    public function saveSheet(Request $request, PackerReport $packerReport)
    {
        $packerNames = $this->packerColumnNames($packerReport);
        if ($packerNames === []) {
            return back()
                ->withInput()
                ->with('error', 'No packer columns available. Check config/packers.php.');
        }

        $request->validate([
            'pack_date' => 'required|date',
            'expiration_date' => 'required|date|after_or_equal:pack_date',
            'notes' => 'nullable|string|max:500',
            'cells' => 'required|array',
            'cells.*' => 'array',
            'cells.*.*' => 'nullable|numeric|min:0|max:999999',
        ], [
            'expiration_date.required' => 'Expiration date is required.',
            'expiration_date.after_or_equal' => 'Expiration must be on or after the pack date.',
        ]);

        // Production-linked: additive mode — each save increments existing packs
        if ($packerReport->daily_production_report_id) {
            return $this->saveSheetAdditive($request, $packerReport, $packerNames);
        }

        $packDate = Carbon::parse($request->pack_date)->format('Y-m-d');
        $expirationDate = Carbon::parse($request->expiration_date)->format('Y-m-d');
        $cells = $request->input('cells', []);
        $products = FinishedProduct::orderBy('name')->get()->keyBy('id');

        $normalized = $this->normalizeCellQuantities($cells, $products, $packerNames);

        try {
            DB::beginTransaction();
            $affectedProductIds = $packerReport->packs()
                ->pluck('finished_product_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $this->revertReportPacksFromStock($packerReport);
            $packerReport->packs()->delete();

            $packerReport->update([
                'pack_date' => $packDate,
                'expiration_date' => $expirationDate,
                'notes' => $request->input('notes'),
            ]);

            $this->applyNormalizedPacks($packerReport, $normalized);
            $affectedProductIds = array_values(array_unique(array_merge(
                $affectedProductIds,
                array_map(fn ($row) => (int) $row['finished_product_id'], $normalized)
            )));
            if ($affectedProductIds !== []) {
                $this->productionPackingSyncService->sync($affectedProductIds);
            }

            // Append a session log snapshot so history is preserved
            if ($normalized !== []) {
                $products = FinishedProduct::whereIn('id', array_unique(array_map(fn ($r) => $r['finished_product_id'], $normalized)))->pluck('name', 'id');
                $snapshot = array_map(fn ($r) => [
                    'finished_product_id' => $r['finished_product_id'],
                    'product_name'        => $products[$r['finished_product_id']] ?? '—',
                    'packer_name'         => $r['packer_name'],
                    'quantity'            => $r['quantity'],
                ], $normalized);

                PackerSessionLog::create([
                    'packer_report_id' => $packerReport->id,
                    'snapshot'         => $snapshot,
                    'saved_by'         => Auth::id(),
                    'notes'            => $request->input('notes'),
                ]);
            }

            DB::commit();

            $msg = $normalized === []
                ? 'Report updated — quantities cleared. Stock was adjusted back.'
                : 'Report updated. '.count($normalized).' cell(s) saved; inventory and daily production balances adjusted.';

            // Redirect back to the production-linked sheet if applicable
            if ($packerReport->daily_production_report_id) {
                return redirect()
                    ->route('packer-packs.sheet', $packerReport)
                    ->with('success', $msg);
            }

            return redirect()
                ->route('packer-packs.index')
                ->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Could not save the packers sheet.');
        }
    }

    /**
     * Additive save for production-linked packer sheets.
     * Each save increments existing packs (not replaces) and logs a snapshot.
     */
    protected function saveSheetAdditive(Request $request, PackerReport $packerReport, array $packerNames): \Illuminate\Http\RedirectResponse
    {
        $packDate       = Carbon::parse($request->pack_date)->format('Y-m-d');
        $expirationDate = Carbon::parse($request->expiration_date)->format('Y-m-d');
        $cells          = $request->input('cells', []);

        // Only products belonging to this day's daily production
        $dailyReport    = $packerReport->dailyProductionReport()->with('entries')->first();
        $productIds     = $dailyReport ? $dailyReport->entries->pluck('finished_product_id')->all() : [];
        $products       = FinishedProduct::whereIn('id', $productIds)->get()->keyBy('id');

        $normalized = $this->normalizeCellQuantities($cells, $products, $packerNames);

        if ($normalized === []) {
            return redirect()
                ->route('packer-packs.sheet', $packerReport)
                ->with('error', 'No quantities entered. Please fill in at least one packer cell before saving.');
        }

        try {
            DB::beginTransaction();

            $packerReport->update([
                'pack_date'       => $packDate,
                'expiration_date' => $expirationDate,
            ]);

            $affectedProductIds = [];

            foreach ($normalized as $row) {
                $pid         = (int) $row['finished_product_id'];
                $packerName  = $row['packer_name'];
                $increment   = (float) $row['quantity'];

                // Increment or create the pack row
                $existingPack = PackerPack::where('packer_report_id', $packerReport->id)
                    ->where('finished_product_id', $pid)
                    ->where('packer_name', $packerName)
                    ->lockForUpdate()
                    ->first();

                if ($existingPack) {
                    $existingPack->quantity = round((float) $existingPack->quantity + $increment, 2);
                    $existingPack->save();
                } else {
                    PackerPack::create([
                        'packer_report_id'    => $packerReport->id,
                        'finished_product_id' => $pid,
                        'packer_name'         => $packerName,
                        'quantity'            => $increment,
                        'user_id'             => Auth::id(),
                    ]);
                }

                // Apply increment directly to finished-product stock
                $product = FinishedProduct::whereKey($pid)->lockForUpdate()->first();
                if ($product) {
                    $product->stock_on_hand = round((float) $product->stock_on_hand + $increment, 4);
                    $product->save();
                }

                $affectedProductIds[] = $pid;
            }

            $affectedProductIds = array_values(array_unique($affectedProductIds));
            if ($affectedProductIds !== []) {
                $this->productionPackingSyncService->sync($affectedProductIds);
            }

            // Log this session snapshot (records increments, not totals)
            $productNames = FinishedProduct::whereIn('id', $affectedProductIds)->pluck('name', 'id');
            $snapshot = array_map(fn ($r) => [
                'finished_product_id' => $r['finished_product_id'],
                'product_name'        => $productNames[$r['finished_product_id']] ?? '—',
                'packer_name'         => $r['packer_name'],
                'quantity'            => $r['quantity'],
            ], $normalized);

            PackerSessionLog::create([
                'packer_report_id' => $packerReport->id,
                'snapshot'         => $snapshot,
                'saved_by'         => Auth::id(),
                'notes'            => $request->input('notes'),
            ]);

            DB::commit();

            $totalQty = array_sum(array_column($normalized, 'quantity'));

            return redirect()
                ->route('packer-packs.sheet', $packerReport)
                ->with('success', 'Packing updated — '.number_format($totalQty).' packs added across '.count($normalized).' cell(s). History logged.');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Could not save packing update.');
        }
    }

    public function destroy(PackerReport $packerReport)
    {
        try {
            DB::beginTransaction();
            $affectedProductIds = $packerReport->packs()
                ->pluck('finished_product_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $this->revertReportPacksFromStock($packerReport);
            $packerReport->packs()->delete();
            $packerReport->delete();
            if ($affectedProductIds !== []) {
                $this->productionPackingSyncService->sync($affectedProductIds);
            }

            DB::commit();

            return redirect()
                ->route('packer-packs.index')
                ->with('success', 'Packers report deleted, stock restored, and daily production balances re-synced.');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return back()->with('error', 'Delete failed. Please try again.');
        }
    }

    /**
     * Blanks and non-numeric are treated as 0 — only positive integers create pack lines.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\FinishedProduct>  $products
     * @return list<array{finished_product_id:int, packer_name:string, quantity:float}>
     */
    protected function normalizeCellQuantities(array $cells, $products, array $packerNames): array
    {
        $normalized = [];
        foreach ($products as $pid => $product) {
            foreach ($packerNames as $packer) {
                $raw = $cells[$pid][$packer] ?? null;
                if ($raw === null || $raw === '') {
                    continue;
                }
                $qty = (int) round((float) $raw);
                if ($qty <= 0) {
                    continue;
                }
                $normalized[] = [
                    'finished_product_id' => (int) $pid,
                    'packer_name' => $packer,
                    'quantity' => (float) $qty,
                ];
            }
        }

        return $normalized;
    }

    /**
     * Config packers first, then any packer names already on this report (so edits do not drop legacy columns).
     *
     * @return list<string>
     */
    protected function packerColumnNames(?PackerReport $report): array
    {
        $configNames = array_values(array_filter(
            config('packers.names', []),
            fn ($n) => is_string($n) && trim($n) !== ''
        ));

        if (! $report) {
            return $configNames;
        }

        $report->loadMissing('packs');
        $extras = $report->packs
            ->pluck('packer_name')
            ->filter()
            ->unique()
            ->reject(fn ($n) => in_array($n, $configNames, true))
            ->values()
            ->all();

        return array_values(array_merge($configNames, $extras));
    }

    protected function revertReportPacksFromStock(PackerReport $packerReport): void
    {
        $packerReport->loadMissing('packs');

        foreach ($packerReport->packs as $pack) {
            $product = FinishedProduct::query()
                ->whereKey($pack->finished_product_id)
                ->lockForUpdate()
                ->first();

            if ($product) {
                $product->stock_on_hand = round((float) $product->stock_on_hand - (float) $pack->quantity, 4);
                $product->save();
            }
        }
    }

    /**
     * @param  list<array{finished_product_id:int, packer_name:string, quantity:float}>  $normalized
     */
    protected function applyNormalizedPacks(PackerReport $report, array $normalized): void
    {
        foreach ($normalized as $row) {
            PackerPack::create([
                'packer_report_id' => $report->id,
                'finished_product_id' => $row['finished_product_id'],
                'packer_name' => $row['packer_name'],
                'quantity' => $row['quantity'],
                'user_id' => Auth::id(),
                'notes' => null,
            ]);

            $product = FinishedProduct::query()
                ->whereKey($row['finished_product_id'])
                ->lockForUpdate()
                ->first();

            if ($product) {
                $add = (float) $row['quantity'];
                $product->stock_on_hand = round((float) $product->stock_on_hand + $add, 4);
                $product->save();
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\FinishedProduct>  $products
     * @return array<int, array{value:float, unit:string}>
     */
    protected function overallRemainingByProduct($products): array
    {
        $totals = DailyProductionEntry::query()
            ->whereIn('finished_product_id', $products->pluck('id')->all())
            ->selectRaw('finished_product_id, COALESCE(SUM(unpacked), 0) as unpacked_total')
            ->groupBy('finished_product_id')
            ->pluck('unpacked_total', 'finished_product_id');

        $out = [];
        foreach ($products as $product) {
            $unpackedPieces = (float) ($totals[$product->id] ?? 0);
            $meta = $this->remainingDisplayMeta((string) $product->name);
            $out[$product->id] = [
                'value' => round($unpackedPieces * $meta['multiplier'], 2),
                'unit' => $meta['unit'],
            ];
        }

        return $out;
    }

    /**
     * @return array{unit:string, multiplier:float}
     */
    protected function remainingDisplayMeta(string $productName): array
    {
        $name = strtolower(trim($productName));
        $rules = config('pack_standards.rules', []);

        foreach ($rules as $rule) {
            $keywords = array_values(array_filter($rule['keywords'] ?? [], fn ($k) => is_string($k) && trim($k) !== ''));
            if ($keywords === []) {
                continue;
            }

            $matches = true;
            foreach ($keywords as $keyword) {
                if (! str_contains($name, strtolower($keyword))) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                $unit = strtolower((string) ($rule['remaining_unit'] ?? 'pcs'));
                $multiplier = (float) ($rule['remaining_multiplier'] ?? 1);
                if ($multiplier <= 0) {
                    $multiplier = 1;
                }

                return [
                    'unit' => $unit === 'g' ? 'g' : 'pcs',
                    'multiplier' => $multiplier,
                ];
            }
        }

        return ['unit' => 'pcs', 'multiplier' => 1];
    }
}
