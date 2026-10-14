<?php
declare(strict_types=1);

// Preset de teste com settings.order, order_past e temporal (não é distribuído).
return [
    'key' => 'evt',
    'slug' => 'evts',
    'labels' => ['singular' => 'Evento', 'plural' => 'Eventos'],
    'groups' => ['principal' => 'Dados principais', 'privado' => 'Notas internas'],
    'fields' => [
        'data_inicio' => ['type' => 'date', 'label' => 'Início', 'required' => true],
        'data_fim' => ['type' => 'date', 'label' => 'Fim'],
        'hora_inicio' => ['type' => 'time', 'label' => 'Hora'],
        'local' => ['type' => 'text', 'label' => 'Local'],
        'lotacao' => ['type' => 'number', 'label' => 'Lotação'],
        'galeria' => ['type' => 'gallery', 'label' => 'Galeria'],
        'segredo' => ['type' => 'date', 'label' => 'Data privada', 'group' => 'privado', 'public' => false],
    ],
    'settings' => [
        'temporal' => ['field' => 'data_inicio', 'end_field' => 'data_fim', 'undated' => 'upcoming', 'archive_when' => 'upcoming'],
        'order' => [
            ['by' => 'data_inicio', 'dir' => 'ASC'],
            ['by' => 'hora_inicio', 'dir' => 'ASC', 'missing' => 'first'],
            ['by' => '@title', 'dir' => 'ASC'],
            ['by' => 'segredo', 'dir' => 'ASC'],
            ['by' => 'galeria', 'dir' => 'ASC'],
            ['by' => 'nao_existe', 'dir' => 'ASC'],
        ],
        'order_past' => [['by' => 'data_inicio', 'dir' => 'DESC', 'missing' => 'hide']],
    ],
];
