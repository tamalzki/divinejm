<?php

namespace App\Http\Controllers;

use App\Models\FinishedProduct;
use App\Models\PackerPack;
use App\Models\PackerReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PackerReportController extends Controller
{
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

            DB::commit();

            $msg = 'Packers report created.'.(count($normalized) > 0 ? ' '.count($normalized).' cell(s); inventory updated.' : '');

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
            'defaultPackDate' => $packerReport->pack_date->format('Y-m-d'),
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

        $packDate = Carbon::parse($request->pack_date)->format('Y-m-d');
        $expirationDate = Carbon::parse($request->expiration_date)->format('Y-m-d');
        $cells = $request->input('cells', []);
        $products = FinishedProduct::orderBy('name')->get()->keyBy('id');

        $normalized = $this->normalizeCellQuantities($cells, $products, $packerNames);

        try {
            DB::beginTransaction();

            $this->revertReportPacksFromStock($packerReport);
            $packerReport->packs()->delete();

            $packerReport->update([
                'pack_date' => $packDate,
                'expiration_date' => $expirationDate,
                'notes' => $request->input('notes'),
            ]);

            $this->applyNormalizedPacks($packerReport, $normalized);

            DB::commit();

            $msg = $normalized === []
                ? 'Report updated — quantities cleared. Stock was adjusted back.'
                : 'Report updated. '.count($normalized).' cell(s) saved; inventory adjusted.';

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

    public function destroy(PackerReport $packerReport)
    {
        try {
            DB::beginTransaction();

            $this->revertReportPacksFromStock($packerReport);
            $packerReport->packs()->delete();
            $packerReport->delete();

            DB::commit();

            return redirect()
                ->route('packer-packs.index')
                ->with('success', 'Packers report deleted and stock restored.');
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
}
