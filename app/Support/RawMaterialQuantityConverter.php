<?php

namespace App\Support;

/**
 * Converts a quantity from an input unit to the raw material's storage unit (inventory unit).
 * Supports mass (KG↔G) and volume (L↔ML). Count units must match storage (PCS, REAM, etc.).
 */
final class RawMaterialQuantityConverter
{
    /** @var list<string> */
    private const MASS = ['KG', 'G'];

    /** @var list<string> */
    private const VOLUME = ['L', 'ML'];

    /** @var list<string> */
    private const COUNT = ['PCS', 'PACKS', 'REAM', 'ROLL', 'BOX', 'SACK'];

    /**
     * @return array{0: float, 1: string|null} [storageQuantity, errorMessage]
     */
    public static function convertToStorage(float $quantity, string $fromUnit, string $storageUnit): array
    {
        $from = self::canonical($fromUnit);
        $to = self::canonical($storageUnit);

        if ($from === null || $to === null) {
            return [0.0, 'Unknown unit.'];
        }

        if ($from === $to) {
            return [round($quantity, 6), null];
        }

        if (self::inGroup(self::MASS, $from) && self::inGroup(self::MASS, $to)) {
            $g = self::toGrams($quantity, $from);

            return [round(self::fromGrams($g, $to), 6), null];
        }

        if (self::inGroup(self::VOLUME, $from) && self::inGroup(self::VOLUME, $to)) {
            $ml = self::toMilliliters($quantity, $from);

            return [round(self::fromMilliliters($ml, $to), 6), null];
        }

        if (self::inGroup(self::COUNT, $from) && self::inGroup(self::COUNT, $to) && $from === $to) {
            return [round($quantity, 6), null];
        }

        return [0.0, "Cannot convert from {$from} to {$to} — use the same kind of unit as inventory (e.g. G vs KG for weight)."];
    }

    public static function canonical(?string $unit): ?string
    {
        if ($unit === null || $unit === '') {
            return null;
        }
        $trim = trim((string) $unit);
        $resolved = RawMaterialUnit::resolveToCanonical($trim);

        return $resolved ?? strtoupper($trim);
    }

    /** @param list<string> $group */
    private static function inGroup(array $group, string $u): bool
    {
        return in_array($u, $group, true);
    }

    private static function toGrams(float $q, string $u): float
    {
        return match ($u) {
            'KG' => $q * 1000,
            'G' => $q,
            default => 0.0,
        };
    }

    private static function fromGrams(float $g, string $u): float
    {
        return match ($u) {
            'KG' => $g / 1000,
            'G' => $g,
            default => 0.0,
        };
    }

    private static function toMilliliters(float $q, string $u): float
    {
        return match ($u) {
            'L' => $q * 1000,
            'ML' => $q,
            default => 0.0,
        };
    }

    private static function fromMilliliters(float $ml, string $u): float
    {
        return match ($u) {
            'L' => $ml / 1000,
            'ML' => $ml,
            default => 0.0,
        };
    }
}
