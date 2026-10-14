<?php
declare(strict_types=1);

// Temporal sem end_field e com undated = past.
return [
    'key' => 'simples',
    'slug' => 'simples',
    'labels' => ['singular' => 'S', 'plural' => 'Ss'],
    'fields' => ['quando' => ['type' => 'date', 'label' => 'Quando'], 'texto' => ['type' => 'text', 'label' => 'Texto']],
    'settings' => ['temporal' => ['field' => 'quando', 'undated' => 'past']],
];
