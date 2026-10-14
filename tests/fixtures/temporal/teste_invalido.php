<?php
declare(strict_types=1);

// temporal aponta para um campo que não é date: a primitiva fica desligada (sem erro).
return [
    'key' => 'invtemp',
    'slug' => 'invtemp',
    'labels' => ['singular' => 'S', 'plural' => 'Ss'],
    'fields' => ['texto' => ['type' => 'text', 'label' => 'Texto']],
    'settings' => ['temporal' => ['field' => 'texto'], 'order' => 'isto-nao-e-lista'],
];
