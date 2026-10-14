<?php
declare(strict_types=1);

/**
 * Preset Serviços · servico
 *
 * Para qualquer negócio ou profissional que apresente o que faz: consultas, tratamentos, aulas,
 * assessoria, projetos sob medida. Arquivo /servicos/, página individual /servicos/<slug>/.
 * O preço é opcional: um serviço pode existir sem preço. A ordem no site é a do campo "Ordem" (Atributos).
 *
 * Não confundir com "plano": o plano é um pacote comercial com preço e periodicidade (mensalidade,
 * pacote de sessões). O serviço é a atividade ou oferta profissional.
 *
 * @since 3.4.0
 */
return [
    'key' => 'servico',
    'post_type' => 'fs_servico',
    'slug' => 'servicos',
    'labels' => [
        'singular' => 'Serviço',
        'plural' => 'Serviços',
        'add_new' => 'Adicionar serviço',
        'add_new_item' => 'Adicionar serviço',
        'edit_item' => 'Editar serviço',
        'new_item' => 'Novo serviço',
        'not_found' => 'Ainda não há serviços.',
        'all_items' => 'Todos os serviços',
        'title_placeholder' => 'Ex.: Consulta de avaliação',
        'editor_label' => 'Descrição do serviço',
    ],
    'menu_icon' => 'dashicons-clipboard',
    'schema' => 'Service',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields', 'page-attributes'],

    'groups' => [
        'principal' => 'Serviço',
        'detalhes' => 'Como funciona',
        'preco' => 'Preço (opcional)',
        'privado' => 'Notas internas',
    ],

    'taxonomies' => [
        'categoria' => [
            'label' => 'Categorias',
            'singular' => 'Categoria',
            'single' => true,
            'locked' => false,
            'admin_column' => true,
            'help' => 'Agrupa os serviços na listagem. Pode criar as suas.',
        ],
    ],

    'fields' => [
        // Serviço
        'resumo' => ['type' => 'text', 'label' => 'Frase curta', 'group' => 'principal', 'max_length' => 160, 'placeholder' => 'Ex.: Primeira avaliação, com plano de acompanhamento.'],
        'para_quem' => ['type' => 'text', 'label' => 'Para quem é', 'group' => 'principal', 'max_length' => 160, 'placeholder' => 'Ex.: Quem procura acompanhamento regular.'],
        'icone' => ['type' => 'image', 'label' => 'Ícone ou imagem pequena', 'group' => 'principal', 'help' => 'Opcional. A foto de destaque do serviço vai na imagem de capa.'],
        'destaque' => ['type' => 'bool', 'label' => 'Serviço em destaque', 'group' => 'principal', 'placement' => 'side', 'admin_column' => true, 'column_label' => 'Destaque'],
        'etiqueta' => ['type' => 'text', 'label' => 'Etiqueta', 'group' => 'principal', 'placement' => 'side', 'max_length' => 30, 'placeholder' => 'Ex.: Mais procurado'],

        // Como funciona
        'como_funciona' => ['type' => 'list', 'label' => 'Como funciona', 'group' => 'detalhes', 'help' => 'Um passo por linha.'],
        'inclui' => ['type' => 'list', 'label' => 'O que inclui', 'group' => 'detalhes', 'help' => 'Um item por linha. Os primeiros aparecem nos cartões do site.'],
        'duracao' => ['type' => 'text', 'label' => 'Duração', 'group' => 'detalhes', 'max_length' => 60, 'placeholder' => 'Ex.: 50 minutos'],
        'botao_link' => ['type' => 'url', 'label' => 'Link do botão (opcional)', 'group' => 'detalhes', 'placeholder' => 'https://', 'help' => 'Vazio: o botão leva ao formulário de contacto.'],

        // Preço
        'preco' => ['type' => 'money', 'label' => 'Preço', 'group' => 'preco', 'min' => 0, 'placeholder' => 'Ex.: 60,00', 'admin_column' => true],
        'preco_desde' => ['type' => 'bool', 'label' => 'Mostrar "desde" antes do preço', 'group' => 'preco'],
        'preco_sob_consulta' => ['type' => 'bool', 'label' => 'Preço sob consulta', 'group' => 'preco', 'help' => 'O preço deixa de aparecer no site.'],

        // Privado
        'notas_internas' => ['type' => 'textarea', 'label' => 'Notas internas', 'group' => 'privado', 'public' => false, 'help' => 'Só visível no painel. Nunca aparece no site.'],
    ],

    'settings' => [
        'price' => ['field' => 'preco', 'flag' => 'preco_sob_consulta'],
        'price_prefix' => ['field' => 'preco_desde', 'text' => 'desde '],
        'featured_field' => 'destaque',
        'model_pages' => [
            'item' => 'Modelo · Serviço',
            'card' => 'Modelo · Cartão de serviço',
            'archive_top' => 'Modelo · Serviços topo',
            'archive_bottom' => 'Modelo · Serviços rodapé',
        ],
        'archive_per_page' => 24,
        'archive_texts' => [
            'empty' => 'Ainda não há serviços publicados.',
            'show_all' => 'Ver todos os serviços',
        ],
        // Sem a regra "preço obrigatório": o serviço pode existir sem preço.
    ],

    // Brasil: textos.
    'markets' => [
        'BR' => [
            'fields' => [
                'botao_link' => ['help' => 'Vazio: o botão leva ao formulário de contato.'],
            ],
        ],
    ],
];
