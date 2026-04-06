<?php

/**
 * Allowed units of measure for raw materials (inventory is stored in this unit).
 * Keys are stored in the database; values are human-readable labels in the UI.
 */
return [
    'units' => [
        'KG' => 'Kilogram (KG)',
        'G' => 'Gram (G)',
        'PCS' => 'Pieces (PCS)',
        'PACKS' => 'Packs (PACKS)',
        'REAM' => 'Ream (REAM)',
        'ROLL' => 'Roll (ROLL)',
        'L' => 'Liter (L)',
        'ML' => 'Milliliter (ML)',
        'BOX' => 'Box (BOX)',
        'SACK' => 'Sack (SACK)',
    ],

    /*
    | Map free-text / legacy labels (lowercase) to canonical unit keys above.
    | Used when editing records saved before the dropdown (e.g. "g", "grams", "kg").
    */
    'unit_aliases' => [
        'kg' => 'KG',
        'kilogram' => 'KG',
        'kilograms' => 'KG',
        'kilo' => 'KG',
        'kilos' => 'KG',
        'kgs' => 'KG',

        'g' => 'G',
        'gram' => 'G',
        'grams' => 'G',
        'gr' => 'G',
        'gm' => 'G',

        'pc' => 'PCS',
        'pcs' => 'PCS',
        'piece' => 'PCS',
        'pieces' => 'PCS',
        'piieces' => 'PCS',

        'pack' => 'PACKS',
        'packs' => 'PACKS',
        'packing' => 'PACKS',

        'ream' => 'REAM',

        'roll' => 'ROLL',
        'rolls' => 'ROLL',

        'l' => 'L',
        'liter' => 'L',
        'litre' => 'L',
        'liters' => 'L',
        'litres' => 'L',

        'ml' => 'ML',
        'milliliter' => 'ML',
        'millilitre' => 'ML',
        'milliliters' => 'ML',
        'millilitres' => 'ML',

        'box' => 'BOX',
        'boxes' => 'BOX',
        'bx' => 'BOX',

        'sack' => 'SACK',
        'sacks' => 'SACK',
        'bag' => 'SACK',
        'bags' => 'SACK',
    ],
];
