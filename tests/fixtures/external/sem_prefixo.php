<?php
declare(strict_types=1);

// Sem o prefixo x_: tem de ser recusado.
return [
    'key' => 'agenda',
    'slug' => 'agenda-sem-prefixo',
    'labels' => ['singular' => 'A', 'plural' => 'B'],
    'fields' => ['a' => ['type' => 'text', 'label' => 'A']],
];
