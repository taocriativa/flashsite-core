<?php
declare(strict_types=1);

/**
 * Preset Projetos · projeto
 *
 * Portfólio, trabalhos, casos, obras e projetos entregues: arquitetos, designers, agências,
 * fotógrafos, artistas, consultores. Arquivo /projetos/, página individual /projetos/<slug>/.
 * A primeira foto da galeria é a capa. A ordem no site é a do campo "Ordem" (Atributos).
 *
 * @since 3.4.0
 */
return [
    'key' => 'projeto',
    'post_type' => 'fs_projeto',
    'slug' => 'projetos',
    'labels' => [
        'singular' => 'Projeto',
        'plural' => 'Projetos',
        'add_new' => 'Adicionar projeto',
        'add_new_item' => 'Adicionar projeto',
        'edit_item' => 'Editar projeto',
        'new_item' => 'Novo projeto',
        'not_found' => 'Ainda não há projetos.',
        'all_items' => 'Todos os projetos',
        'title_placeholder' => 'Ex.: Casa na Serra',
        'editor_label' => 'Descrição do projeto',
    ],
    'menu_icon' => 'dashicons-portfolio',
    'schema' => 'CreativeWork',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields', 'page-attributes'],

    'groups' => [
        'principal' => 'Projeto',
        'detalhes' => 'Detalhes',
        'media' => 'Fotos e vídeo',
        'privado' => 'Notas internas',
    ],

    'taxonomies' => [
        'categoria' => [
            'label' => 'Categorias',
            'singular' => 'Categoria',
            'single' => true,
            'locked' => false,
            'admin_column' => true,
            'help' => 'Permite filtrar os projetos na listagem. Pode criar as suas.',
        ],
    ],

    'fields' => [
        'cliente' => ['type' => 'text', 'label' => 'Cliente', 'group' => 'principal', 'max_length' => 80, 'admin_column' => true],
        // Texto, não número: "2025" não pode aparecer como "2.025" e aceita períodos ("2023 a 2025").
        'ano' => ['type' => 'text', 'label' => 'Ano', 'group' => 'principal', 'max_length' => 20, 'placeholder' => 'Ex.: 2025', 'admin_column' => true],
        'local' => ['type' => 'text', 'label' => 'Local', 'group' => 'principal', 'max_length' => 80],
        'resumo' => ['type' => 'text', 'label' => 'Frase curta', 'group' => 'principal', 'max_length' => 160],
        'destaque' => ['type' => 'bool', 'label' => 'Projeto em destaque', 'group' => 'principal', 'placement' => 'side', 'admin_column' => true, 'column_label' => 'Destaque'],

        'escopo' => ['type' => 'text', 'label' => 'O que foi feito', 'group' => 'detalhes', 'max_length' => 160, 'placeholder' => 'Ex.: Projeto e acompanhamento de obra'],
        'resultado' => ['type' => 'textarea', 'label' => 'Resultado', 'group' => 'detalhes', 'help' => 'Opcional. O que o projeto alcançou.'],
        'link' => ['type' => 'url', 'label' => 'Link do projeto (opcional)', 'group' => 'detalhes', 'placeholder' => 'https://'],

        'galeria' => ['type' => 'gallery', 'label' => 'Fotos', 'group' => 'media', 'help' => 'Arraste para ordenar. A primeira foto é a capa.'],
        'video_url' => ['type' => 'url', 'label' => 'Vídeo (link)', 'group' => 'media', 'placeholder' => 'https://'],

        'notas_internas' => ['type' => 'textarea', 'label' => 'Notas internas', 'group' => 'privado', 'public' => false, 'help' => 'Só visível no painel. Nunca aparece no site.'],
    ],

    'settings' => [
        'cover_from' => 'galeria',
        'featured_field' => 'destaque',
        'schema_map' => [
            'sameAs' => 'link',
        ],
        'model_pages' => [
            'item' => 'Modelo · Projeto',
            'card' => 'Modelo · Cartão de projeto',
            'archive_top' => 'Modelo · Projetos topo',
            'archive_bottom' => 'Modelo · Projetos rodapé',
        ],
        'archive_per_page' => 12,
        'archive_texts' => [
            'empty' => 'Ainda não há projetos publicados.',
            'show_all' => 'Ver todos os projetos',
        ],
    ],
];
