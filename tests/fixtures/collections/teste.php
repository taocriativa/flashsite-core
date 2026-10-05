<?php
declare(strict_types=1);

// Preset de teste do motor (não é distribuído como preset real).
return [
    'key' => 'teste',
    'slug' => 'testes',
    'labels' => ['singular' => 'Teste', 'plural' => 'Testes'],
    'schema' => 'Thing',
    'groups' => ['principal' => 'Dados principais', 'privado' => 'Notas internas'],
    'taxonomies' => [
        'estado' => ['label' => 'Estado', 'terms' => ['disponivel' => 'Disponível', 'vendido' => 'Vendido'], 'single' => true],
        'zona' => ['label' => 'Zona', 'hierarchical' => true],
    ],
    'fields' => [
        'preco' => ['type' => 'money', 'label' => 'Preço', 'min' => 0],
        'preco_sob_consulta' => ['type' => 'bool', 'label' => 'Preço sob consulta'],
        'quartos' => ['type' => 'number', 'label' => 'Quartos', 'min' => 0, 'max' => 50],
        'classe' => ['type' => 'select', 'label' => 'Classe energética', 'options' => ['a+' => 'A+', 'a' => 'A', 'b' => 'B', 'isento' => 'Isento']],
        'alergenios' => ['type' => 'multiselect', 'label' => 'Alergénios', 'options' => ['gluten' => 'Glúten', 'leite' => 'Leite']],
        'galeria' => ['type' => 'gallery', 'label' => 'Galeria'],
        'coordenadas' => ['type' => 'geo', 'label' => 'Coordenadas'],
        'data' => ['type' => 'date', 'label' => 'Data'],
        'hora_inicio' => ['type' => 'time', 'label' => 'Hora de início'],
        'hora_fim' => ['type' => 'time', 'label' => 'Hora de fim'],
        'itens' => ['type' => 'list', 'label' => 'Itens incluídos'],
        'referencia' => ['type' => 'text', 'label' => 'Referência', 'required' => true, 'max_length' => 20],
        'notas_internas' => ['type' => 'textarea', 'label' => 'Notas internas', 'group' => 'privado', 'public' => false],
    ],
    'settings' => [
        'rules' => [
            ['required_unless' => ['preco', 'preco_sob_consulta']],
            ['after_or_equal' => ['hora_fim', 'hora_inicio']],
        ],
    ],
];
