<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Finished Product Pack Standards
    |--------------------------------------------------------------------------
    | Rules are matched by checking if all keywords exist in the finished
    | product name (case-insensitive). First matching rule wins.
    |
    | pcs_per_pack:
    | - Piece-based products use their standard pieces per pack.
    | - 110g products are treated as 1 produced piece per packed unit.
    */
    'rules' => [
        ['keywords' => ['dice'], 'pcs_per_pack' => 6, 'remaining_unit' => 'pcs', 'remaining_multiplier' => 1],
        ['keywords' => ['hopia'], 'pcs_per_pack' => 6, 'remaining_unit' => 'pcs', 'remaining_multiplier' => 1],
        ['keywords' => ['piaya'], 'pcs_per_pack' => 5, 'remaining_unit' => 'pcs', 'remaining_multiplier' => 1],
        ['keywords' => ['otap'], 'pcs_per_pack' => 15, 'remaining_unit' => 'pcs', 'remaining_multiplier' => 1],
        ['keywords' => ['sesame', 'kisses', 'lunga', '110g'], 'pcs_per_pack' => 1, 'remaining_unit' => 'g', 'remaining_multiplier' => 110],
        ['keywords' => ['patatas', '110g'], 'pcs_per_pack' => 1, 'remaining_unit' => 'g', 'remaining_multiplier' => 110],
        ['keywords' => ['sampaguita', '110g'], 'pcs_per_pack' => 1, 'remaining_unit' => 'g', 'remaining_multiplier' => 110],
        ['keywords' => ['butter', 'cookies'], 'pcs_per_pack' => 10, 'remaining_unit' => 'pcs', 'remaining_multiplier' => 1],
    ],
];
