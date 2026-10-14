<?php
declare(strict_types=1);

/**
 * Preset Eventos · evento
 *
 * Espetáculos, palestras, workshops, aulas abertas, noites temáticas, lançamentos.
 * Arquivo /eventos/, página individual /eventos/<slug>/. "Agenda" é um nome possível para a secção ou
 * a página de um site; o endereço do produto é /eventos/.
 *
 * Os eventos passados continuam publicados. A listagem mostra por defeito os próximos
 * (settings.temporal.archive_when); /eventos/?when=past traz o histórico e ?when=all todos.
 * Sem recorrência, reservas nem calendário: cada evento é um item com a sua data.
 *
 * @since 3.4.0
 */
return [
    'key' => 'evento',
    'post_type' => 'fs_evento',
    'slug' => 'eventos',
    'labels' => [
        'singular' => 'Evento',
        'plural' => 'Eventos',
        'add_new' => 'Adicionar evento',
        'add_new_item' => 'Adicionar evento',
        'edit_item' => 'Editar evento',
        'new_item' => 'Novo evento',
        'not_found' => 'Ainda não há eventos.',
        'all_items' => 'Todos os eventos',
        'title_placeholder' => 'Ex.: Workshop de introdução',
        'editor_label' => 'Descrição do evento',
    ],
    'menu_icon' => 'dashicons-calendar-alt',
    'schema' => 'Event',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'],

    'groups' => [
        'principal' => 'Evento',
        'quando' => 'Data e hora',
        'onde' => 'Local',
        'entrada' => 'Entrada',
        'media' => 'Fotos',
        'privado' => 'Notas internas',
    ],

    'taxonomies' => [
        'estado' => [
            'label' => 'Estado',
            'single' => true,
            'required' => true,
            'default' => 'confirmado',
            'terms' => [
                'confirmado' => 'Confirmado',
                'esgotado' => 'Esgotado',
                'adiado' => 'Adiado',
                'cancelado' => 'Cancelado',
            ],
            'admin_column' => true,
            'help' => 'Um evento adiado ou cancelado continua publicado, com o estado visível.',
        ],
        'tipo' => [
            'label' => 'Tipos de evento',
            'singular' => 'Tipo',
            'single' => true,
            'locked' => false,
            'help' => 'Pode criar os seus (ex.: Espetáculo, Workshop).',
        ],
    ],

    'fields' => [
        'resumo' => ['type' => 'text', 'label' => 'Frase curta', 'group' => 'principal', 'max_length' => 160],
        'destaque' => ['type' => 'bool', 'label' => 'Evento em destaque', 'group' => 'principal', 'placement' => 'side', 'admin_column' => true, 'column_label' => 'Destaque'],

        'data_inicio' => ['type' => 'date', 'label' => 'Data de início', 'group' => 'quando', 'required' => true, 'admin_column' => true],
        'hora_inicio' => ['type' => 'time', 'label' => 'Hora de início', 'group' => 'quando'],
        'data_fim' => ['type' => 'date', 'label' => 'Data de fim', 'group' => 'quando', 'help' => 'Só para eventos de vários dias.'],
        'hora_fim' => ['type' => 'time', 'label' => 'Hora de fim', 'group' => 'quando'],

        'local' => ['type' => 'text', 'label' => 'Local', 'group' => 'onde', 'max_length' => 120, 'placeholder' => 'Ex.: Teatro Municipal'],
        'cidade' => ['type' => 'text', 'label' => 'Cidade', 'group' => 'onde', 'max_length' => 60, 'admin_column' => true],
        'mapa_url' => ['type' => 'url', 'label' => 'Link do mapa', 'group' => 'onde', 'placeholder' => 'https://'],

        'preco' => ['type' => 'money', 'label' => 'Preço', 'group' => 'entrada', 'min' => 0, 'placeholder' => 'Ex.: 15,00'],
        'gratuito' => ['type' => 'bool', 'label' => 'Entrada gratuita', 'group' => 'entrada'],
        'ingressos_url' => ['type' => 'url', 'label' => 'Link de ingressos ou inscrição', 'group' => 'entrada', 'placeholder' => 'https://'],

        'galeria' => ['type' => 'gallery', 'label' => 'Fotos', 'group' => 'media', 'help' => 'Arraste para ordenar. A foto de destaque do evento vai na imagem de capa.'],

        'notas_internas' => ['type' => 'textarea', 'label' => 'Notas internas', 'group' => 'privado', 'public' => false, 'help' => 'Só visível no painel. Nunca aparece no site.'],
    ],

    'settings' => [
        'price' => ['field' => 'preco'],
        'featured_field' => 'destaque',
        // Janela temporal (beta.1): data de referência = data de fim, ou a de início.
        'temporal' => ['field' => 'data_inicio', 'end_field' => 'data_fim', 'undated' => 'upcoming', 'archive_when' => 'upcoming'],
        // Próximos: do mais cedo para o mais tarde. Passados: do mais recente para o mais antigo (declarado, sem inversão automática).
        'order' => [
            ['by' => 'data_inicio', 'dir' => 'ASC'],
            ['by' => 'hora_inicio', 'dir' => 'ASC'],
            ['by' => '@title', 'dir' => 'ASC'],
        ],
        'order_past' => [
            ['by' => 'data_inicio', 'dir' => 'DESC'],
            ['by' => 'hora_inicio', 'dir' => 'DESC'],
            ['by' => '@title', 'dir' => 'ASC'],
        ],
        'rules' => [
            ['after_or_equal' => ['data_fim', 'data_inicio']],
        ],
        'schema_availability' => [
            'taxonomy' => 'estado',
            'map' => ['confirmado' => 'InStock', 'esgotado' => 'SoldOut'],
        ],
        'schema_map' => [
            'startDate' => 'data_inicio',
            'endDate' => 'data_fim',
            'location' => 'local',
            'eventStatus' => [
                'taxonomy' => 'estado',
                'map' => [
                    'confirmado' => 'https://schema.org/EventScheduled',
                    'adiado' => 'https://schema.org/EventPostponed',
                    'cancelado' => 'https://schema.org/EventCancelled',
                ],
            ],
        ],
        'model_pages' => [
            'item' => 'Modelo · Evento',
            'card' => 'Modelo · Cartão de evento',
            'archive_top' => 'Modelo · Eventos topo',
            'archive_bottom' => 'Modelo · Eventos rodapé',
        ],
        'archive_per_page' => 12,
        'archive_texts' => [
            'empty' => 'Ainda não há eventos publicados.',
            'show_all' => 'Ver todos os eventos',
        ],
    ],
];
