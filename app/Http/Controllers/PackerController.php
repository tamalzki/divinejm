<?php

namespace App\Http\Controllers;

use App\Models\Packer;
use App\Models\PackerPack;
use App\Models\PackerSessionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PackerController extends Controller
{
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'packers' => 'nullable|array',
            'packers.*.id' => 'nullable|integer|exists:packers,id',
            'packers.*.name' => 'required|string|max:64',
            'removed_ids' => 'nullable|array',
            'removed_ids.*' => 'integer|exists:packers,id',
        ]);

        $rows = collect($validated['packers'] ?? [])
            ->map(fn ($row) => [
                'id' => $row['id'] ?? null,
                'name' => trim($row['name']),
            ])
            ->filter(fn ($row) => $row['name'] !== '')
            ->values();

        $removedIds = collect($validated['removed_ids'] ?? [])->map(fn ($id) => (int) $id);

        // Case-insensitive duplicate check within the submitted set.
        $lowerNames = $rows->pluck('name')->map(fn ($n) => mb_strtolower($n));
        if ($lowerNames->count() !== $lowerNames->unique()->count()) {
            throw ValidationException::withMessages([
                'packers' => 'Packer names must be unique — two rows have the same name.',
            ]);
        }

        // Case-insensitive duplicate check against other still-active packers not being removed/edited here.
        $submittedIds = $rows->pluck('id')->filter()->all();
        $collision = Packer::active()
            ->whereNotIn('id', array_merge($submittedIds, $removedIds->all()))
            ->get()
            ->first(fn ($p) => $lowerNames->contains(mb_strtolower($p->name)));

        if ($collision) {
            throw ValidationException::withMessages([
                'packers' => 'Packer name "'.$collision->name.'" already exists.',
            ]);
        }

        try {
            DB::transaction(function () use ($rows, $removedIds) {
                $maxSort = (int) (Packer::max('sort_order') ?? 0);

                foreach ($rows as $row) {
                    if ($row['id']) {
                        $packer = Packer::findOrFail($row['id']);
                        $oldName = $packer->name;

                        if ($oldName !== $row['name']) {
                            $packer->name = $row['name'];
                            $packer->save();

                            PackerPack::where('packer_name', $oldName)->update(['packer_name' => $row['name']]);

                            PackerSessionLog::whereJsonContains('snapshot', ['packer_name' => $oldName])
                                ->get()
                                ->each(function (PackerSessionLog $log) use ($oldName, $row) {
                                    $snapshot = collect($log->snapshot)->map(function ($entry) use ($oldName, $row) {
                                        if (($entry['packer_name'] ?? null) === $oldName) {
                                            $entry['packer_name'] = $row['name'];
                                        }

                                        return $entry;
                                    })->all();

                                    $log->update(['snapshot' => $snapshot]);
                                });
                        }

                        continue;
                    }

                    // New row — reactivate a matching inactive/active packer instead of duplicating.
                    $existing = Packer::whereRaw('LOWER(name) = ?', [mb_strtolower($row['name'])])->first();
                    if ($existing) {
                        $existing->update(['is_active' => true]);

                        continue;
                    }

                    Packer::create([
                        'name' => $row['name'],
                        'is_active' => true,
                        'sort_order' => ++$maxSort,
                    ]);
                }

                if ($removedIds->isNotEmpty()) {
                    Packer::whereIn('id', $removedIds)->update(['is_active' => false]);
                }
            });
        } catch (\Exception $e) {
            report($e);

            return back()->with('error', 'Could not update packers. Please try again.');
        }

        return back()->with('success', 'Packers updated.');
    }
}
