<?php
declare(strict_types=1);

// Preset externo de teste das Abilities: taxonomia obrigatória sem termo por defeito, campo obrigatório, bool e select.
return [
    'key' => 'x_ficha',
    'slug' => 'fichas-teste',
    'labels' => ['singular' => 'Ficha', 'plural' => 'Fichas'],
    'groups' => ['principal' => 'Dados principais'],
    'taxonomies' => [
        'categoria' => ['label' => 'Categoria', 'single' => true, 'required' => true, 'terms' => ['a' => 'A', 'b' => 'B']],
    ],
    'fields' => [
        'codigo' => ['type' => 'text', 'label' => 'Código', 'required' => true, 'max_length' => 10, 'admin_column' => true],
        'ativo' => ['type' => 'bool', 'label' => 'Ativo'],
        'nivel' => ['type' => 'select', 'label' => 'Nível', 'options' => ['baixo' => 'Baixo', 'alto' => 'Alto']],
    ],
];
