<?php
declare(strict_types=1);

// Preset inválido de propósito: tipo de campo desconhecido. O registry deve ignorá-lo sem fatal.
return [
    'key' => 'invalido',
    'labels' => ['singular' => 'Inválido', 'plural' => 'Inválidos'],
    'fields' => [
        'x' => ['type' => 'inexistente', 'label' => 'X'],
    ],
];
