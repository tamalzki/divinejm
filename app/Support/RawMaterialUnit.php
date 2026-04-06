<?php

namespace App\Support;

final class RawMaterialUnit
{
    /**
     * Map legacy or alternate spellings to a canonical key from config('raw_materials.units').
     */
    public static function resolveToCanonical(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $units = config('raw_materials.units', []);
        $trim = trim((string) $value);
        if ($trim === '') {
            return null;
        }

        if (array_key_exists($trim, $units)) {
            return $trim;
        }

        $upper = strtoupper($trim);
        if (array_key_exists($upper, $units)) {
            return $upper;
        }

        $aliases = config('raw_materials.unit_aliases', []);
        $key = strtolower($trim);
        if (isset($aliases[$key])) {
            $canonical = $aliases[$key];

            return array_key_exists($canonical, $units) ? $canonical : null;
        }

        return null;
    }
}
