<?php
declare(strict_types=1);

// Preset externo válido (kit de teste).
return [
    'key' => 'x_agenda',
    'slug' => 'agenda-teste',
    'labels' => ['singular' => 'Sessão', 'plural' => 'Sessões'],
    'groups' => ['principal' => 'Dados principais'],
    'fields' => ['quando' => ['type' => 'date', 'label' => 'Quando']],
];
