<?php
declare(strict_types=1);

/**
 * Preset Equipa · equipa
 *
 * Para negócios com várias pessoas a apresentar: clínicas, escritórios, escolas, agências, estúdios.
 * Arquivo /equipa/ (/equipe/ no Brasil), página individual /equipa/<slug>/.
 * A foto é a imagem de capa; a biografia é o editor. A ordem no site é a do campo "Ordem" (Atributos).
 *
 * Com uma única pessoa, os Dados do negócio (secção profissional) já chegam: não precisa desta coleção.
 *
 * @since 3.4.0
 */
return [
    'key' => 'equipa',
    'post_type' => 'fs_equipa',
    'slug' => 'equipa',
    'labels' => [
        'singular' => 'Membro',
        'plural' => 'Equipa',
        'add_new' => 'Adicionar membro',
        'add_new_item' => 'Adicionar membro da equipa',
        'edit_item' => 'Editar membro',
        'new_item' => 'Novo membro',
        'not_found' => 'Ainda não há pessoas na equipa.',
        'all_items' => 'Toda a equipa',
        'featured_image' => 'Foto',
        'set_featured_image' => 'Definir foto',
        'title_placeholder' => 'Nome da pessoa',
        'editor_label' => 'Apresentação (biografia)',
    ],
    'menu_icon' => 'dashicons-groups',
    'schema' => 'Person',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields', 'page-attributes'],

    'groups' => [
        'principal' => 'Pessoa',
        'contactos' => 'Contactos e redes',
        'privado' => 'Notas internas',
    ],

    'taxonomies' => [
        'funcao' => [
            'label' => 'Funções',
            'singular' => 'Função',
            'single' => true,
            'locked' => false,
            'admin_column' => true,
            'help' => 'Agrupa as pessoas na listagem. Pode criar as suas.',
        ],
    ],

    'fields' => [
        'cargo' => ['type' => 'text', 'label' => 'Cargo', 'group' => 'principal', 'max_length' => 80, 'placeholder' => 'Ex.: Sócia fundadora', 'admin_column' => true],
        'registro_profissional' => ['type' => 'text', 'label' => 'Registo profissional', 'group' => 'principal', 'max_length' => 60, 'placeholder' => 'Ex.: Cédula n.º 12345', 'help' => 'Opcional. Cédula ou número de inscrição, quando existir.'],
        'especialidade' => ['type' => 'text', 'label' => 'Especialidade', 'group' => 'principal', 'max_length' => 80],
        'resumo' => ['type' => 'text', 'label' => 'Frase curta', 'group' => 'principal', 'max_length' => 160],
        'destaque' => ['type' => 'bool', 'label' => 'Em destaque', 'group' => 'principal', 'placement' => 'side', 'admin_column' => true, 'column_label' => 'Destaque'],

        'email' => ['type' => 'email', 'label' => 'E-mail', 'group' => 'contactos', 'help' => 'Aparece no site. Deixe vazio para não mostrar.'],
        'telefone' => ['type' => 'tel', 'label' => 'Telefone', 'group' => 'contactos', 'help' => 'Aparece no site. Deixe vazio para não mostrar.'],
        'linkedin' => ['type' => 'url', 'label' => 'LinkedIn', 'group' => 'contactos', 'placeholder' => 'https://'],
        'instagram' => ['type' => 'url', 'label' => 'Instagram', 'group' => 'contactos', 'placeholder' => 'https://'],
        'agenda_url' => ['type' => 'url', 'label' => 'Link para marcar', 'group' => 'contactos', 'placeholder' => 'https://', 'help' => 'Opcional. Página ou serviço de marcações desta pessoa.'],

        'notas_internas' => ['type' => 'textarea', 'label' => 'Notas internas', 'group' => 'privado', 'public' => false, 'help' => 'Só visível no painel. Nunca aparece no site.'],
    ],

    'settings' => [
        'featured_field' => 'destaque',
        'schema_map' => [
            'jobTitle' => 'cargo',
            'knowsAbout' => 'especialidade',
            'email' => 'email',
            'telephone' => 'telefone',
        ],
        'model_pages' => [
            'item' => 'Modelo · Membro da equipa',
            'card' => 'Modelo · Cartão da equipa',
            'archive_top' => 'Modelo · Equipa topo',
            'archive_bottom' => 'Modelo · Equipa rodapé',
        ],
        'archive_per_page' => 24,
        'archive_columns' => 3,
        'archive_texts' => [
            'empty' => 'Ainda não há pessoas na equipa.',
            'show_all' => 'Ver toda a equipa',
        ],
    ],

    // Brasil: equipe, registro e contato.
    'markets' => [
        'BR' => [
            'slug' => 'equipe',
            'labels' => [
                'plural' => 'Equipe',
                'not_found' => 'Ainda não há pessoas na equipe.',
                'all_items' => 'Toda a equipe',
                'add_new_item' => 'Adicionar membro da equipe',
                'editor_label' => 'Apresentação (biografia)',
            ],
            'groups' => [
                'contactos' => 'Contatos e redes',
            ],
            'fields' => [
                'cargo' => ['placeholder' => 'Ex.: Sócia fundadora'],
                'registro_profissional' => ['label' => 'Registro profissional', 'placeholder' => 'Ex.: CRM 12345', 'help' => 'Opcional. Número do conselho profissional (CRM, OAB, CRP etc.), quando existir.'],
            ],
            'settings' => [
                'model_pages' => [
                    'item' => 'Modelo · Membro da equipe',
                    'card' => 'Modelo · Cartão da equipe',
                    'archive_top' => 'Modelo · Equipe topo',
                    'archive_bottom' => 'Modelo · Equipe rodapé',
                ],
                'archive_texts' => [
                    'empty' => 'Ainda não há pessoas na equipe.',
                    'show_all' => 'Ver toda a equipe',
                ],
            ],
        ],
    ],
];
