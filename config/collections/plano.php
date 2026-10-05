<?php
declare(strict_types=1);

/**
 * Preset Planos e pacotes · plano
 *
 * Para treinadores, estúdios, explicadores, terapeutas e qualquer negócio que venda planos,
 * pacotes ou mensalidades. Arquivo /planos/, página individual /planos/<slug>/.
 * A ordem no site é a do campo "Ordem" (Atributos) no painel.
 *
 * @since 2.6.0
 */
return [
    'key' => 'plano',
    'post_type' => 'fs_plano',
    'slug' => 'planos',
    'labels' => [
        'singular' => 'Plano',
        'plural' => 'Planos',
        'add_new' => 'Adicionar plano',
        'add_new_item' => 'Adicionar plano',
        'edit_item' => 'Editar plano',
        'new_item' => 'Novo plano',
        'not_found' => 'Ainda não há planos.',
        'all_items' => 'Todos os planos',
        'title_placeholder' => 'Ex.: Plano Híbrido',
        'editor_label' => 'Descrição do plano',
    ],
    'menu_icon' => 'dashicons-tickets-alt',
    'schema' => 'Service',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields', 'page-attributes'],

    'groups' => [
        'principal' => 'Preço',
        'detalhes' => 'O que inclui',
        'privado' => 'Notas internas',
    ],

    'taxonomies' => [
        'modalidade' => [
            'label' => 'Modalidade',
            'single' => true,
            'terms' => [
                'presencial' => 'Presencial',
                'online' => 'Online',
                'hibrido' => 'Híbrido',
                'grupo' => 'Pequeno grupo',
            ],
            'admin_column' => true,
            'help' => 'Pode criar outras modalidades.',
            'locked' => false,
        ],
    ],

    'fields' => [
        // Preço
        'preco' => ['type' => 'money', 'label' => 'Preço', 'group' => 'principal', 'min' => 0, 'placeholder' => 'Ex.: 120,00', 'admin_column' => true],
        'periodicidade' => [
            'type' => 'select', 'label' => 'Periodicidade', 'group' => 'principal',
            'options' => ['mes' => 'por mês', 'semana' => 'por semana', 'sessao' => 'por sessão', 'pacote' => 'por pacote', 'ano' => 'por ano', 'unico' => 'pagamento único'],
            'help' => 'Aparece com o preço: "120,00 €/mês".',
        ],
        'preco_desde' => ['type' => 'bool', 'label' => 'Mostrar "desde" antes do preço', 'group' => 'principal'],
        'preco_sob_consulta' => ['type' => 'bool', 'label' => 'Preço sob consulta', 'group' => 'principal', 'help' => 'O preço deixa de aparecer no site.'],
        'destaque' => ['type' => 'bool', 'label' => 'Plano em destaque', 'group' => 'principal', 'placement' => 'side', 'admin_column' => true, 'column_label' => 'Destaque'],
        'etiqueta' => ['type' => 'text', 'label' => 'Etiqueta', 'group' => 'principal', 'placement' => 'side', 'max_length' => 30, 'placeholder' => 'Ex.: Mais escolhido'],

        // O que inclui
        'resumo' => ['type' => 'text', 'label' => 'Frase curta', 'group' => 'detalhes', 'max_length' => 120, 'placeholder' => 'Ex.: Treino no estúdio e plano na app entre sessões.'],
        'frequencia' => ['type' => 'text', 'label' => 'Frequência', 'group' => 'detalhes', 'max_length' => 60, 'placeholder' => 'Ex.: 2 sessões por semana'],
        'duracao' => ['type' => 'text', 'label' => 'Duração', 'group' => 'detalhes', 'max_length' => 40, 'placeholder' => 'Ex.: 60 min por sessão'],
        'inclui' => ['type' => 'list', 'label' => 'O que inclui', 'group' => 'detalhes', 'help' => 'Um item por linha. Os primeiros aparecem nos cartões do site.'],
        'botao_link' => ['type' => 'url', 'label' => 'Link do botão (opcional)', 'group' => 'detalhes', 'placeholder' => 'https://', 'help' => 'Vazio: o botão leva ao formulário de contacto.'],

        // Privado
        'notas_internas' => ['type' => 'textarea', 'label' => 'Notas internas', 'group' => 'privado', 'public' => false, 'help' => 'Só visível no painel. Nunca aparece no site.'],
    ],

    'settings' => [
        'price' => ['field' => 'preco', 'flag' => 'preco_sob_consulta'],
        'price_prefix' => ['field' => 'preco_desde', 'text' => 'desde '],
        'price_suffix' => ['field' => 'periodicidade', 'map' => ['mes' => '/mês', 'semana' => '/semana', 'sessao' => '/sessão', 'pacote' => '/pacote', 'ano' => '/ano']],
        'featured_field' => 'destaque',
        'model_pages' => [
            'item' => 'Modelo · Plano',
            'card' => 'Modelo · Cartão de plano',
            'archive_top' => 'Modelo · Planos topo',
            'archive_bottom' => 'Modelo · Planos rodapé',
        ],
        'archive_per_page' => 24,
        'archive_filters' => false,
        'archive_texts' => [
            'empty' => 'Ainda não há planos publicados.',
            'show_all' => 'Ver todos os planos',
        ],
        'rules' => [
            ['required_unless' => ['preco', 'preco_sob_consulta']],
        ],
    ],
];
