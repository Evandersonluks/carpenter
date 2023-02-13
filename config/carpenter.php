<?php

// Example
// 'Study|Estudo' => [ ---------------------------------> Entity | Entity translated : string
//     'fields' => [
//         'title|Título' => [ -----------------------------> Column name | Column name translated : string
//             'type' => 'string|string', ------------------> Column type | Form and Filter type : string
//             'migration' => ['->nullable()', ...], ------------> Migration aditional method : array
//             'request' => ['min:1', 'max:255', ...] -----------> FormRequest propeties : array (if not contains migration key, this array starts with 'required')
//         ],
//     ],
//     'pivot' => 'pivots' ---------------------------------------> Pivot table (if exists)
// ]

return [
    //
];