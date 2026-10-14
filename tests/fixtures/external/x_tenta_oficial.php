<?php
declare(strict_types=1);

// Tenta ocupar a chave "teste" de um preset oficial: a chave nem começa por x_.
return [
    'key' => 'teste',
    'slug' => 'outro',
    'labels' => ['singular' => 'A', 'plural' => 'B'],
    'fields' => ['a' => ['type' => 'text', 'label' => 'A']],
];
