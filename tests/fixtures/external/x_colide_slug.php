<?php
declare(strict_types=1);

// Colide por slug com o preset "teste" do Core de teste (/testes/).
return [
    'key' => 'x_colide',
    'slug' => 'testes',
    'labels' => ['singular' => 'A', 'plural' => 'B'],
    'fields' => ['a' => ['type' => 'text', 'label' => 'A']],
];
