<?php
declare(strict_types=1);

/**
 * Preset Imobiliário · imovel
 *
 * Arquivo /imoveis/, página individual /imoveis/<slug>/.
 * A descrição do imóvel é o conteúdo do post (editor), lido pelo widget "Post Content" do Elementor.
 *
 * @since 2.6.0
 */
return [
    'key' => 'imovel',
    'post_type' => 'fs_imovel',
    'slug' => 'imoveis',
    'labels' => [
        'singular' => 'Imóvel',
        'plural' => 'Imóveis',
        'add_new' => 'Adicionar imóvel',
        'add_new_item' => 'Adicionar imóvel',
        'edit_item' => 'Editar imóvel',
        'new_item' => 'Novo imóvel',
        'not_found' => 'Ainda não há imóveis.',
        'all_items' => 'Todos os imóveis',
        'title_placeholder' => 'Ex.: Apartamento T2 com varanda em Alvalade',
        'editor_label' => 'Descrição do imóvel',
    ],
    'menu_icon' => 'dashicons-building',
    'schema' => 'RealEstateListing',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'],

    'groups' => [
        'principal' => 'Dados principais',
        'areas' => 'Áreas e divisões',
        'localizacao' => 'Localização',
        'media' => 'Fotos e vídeo',
        'privado' => 'Notas internas',
    ],

    'taxonomies' => [
        'finalidade' => [
            'label' => 'Finalidade',
            'single' => true,
            'required' => true,
            'default' => 'venda',
            'terms' => ['venda' => 'Venda', 'arrendamento' => 'Arrendamento'],
            'admin_column' => true,
        ],
        'tipo' => [
            'label' => 'Tipo de imóvel',
            'singular' => 'Tipo',
            'single' => true,
            'terms' => [
                'apartamento' => 'Apartamento',
                'moradia' => 'Moradia',
                'terreno' => 'Terreno',
                'loja' => 'Loja',
                'escritorio' => 'Escritório',
            ],
        ],
        'tipologia' => [
            'label' => 'Tipologia',
            'single' => true,
            'terms' => ['t0' => 'T0', 't1' => 'T1', 't2' => 'T2', 't3' => 'T3', 't4' => 'T4', 't5' => 'T5+'],
            'help' => 'Não se aplica a terrenos, lojas e escritórios.',
            'admin_column' => true,
        ],
        'estado' => [
            'label' => 'Estado',
            'single' => true,
            'required' => true,
            'default' => 'disponivel',
            'terms' => [
                'disponivel' => 'Disponível',
                'reservado' => 'Reservado',
                'vendido' => 'Vendido',
                'arrendado' => 'Arrendado',
            ],
            'admin_column' => true,
        ],
        'zona' => [
            'label' => 'Zonas',
            'singular' => 'Zona',
            'hierarchical' => true,
            'locked' => false,
            'help' => 'Concelho e, por baixo, a freguesia. Pode criar novas zonas.',
            'admin_column' => true,
        ],
        'faixa' => [
            'label' => 'Faixa de preço',
            'singular' => 'Faixa de preço',
            'auto' => true,
        ],
    ],

    'fields' => [
        // Dados principais
        'referencia' => ['type' => 'text', 'label' => 'Referência', 'group' => 'principal', 'max_length' => 30, 'placeholder' => 'Ex.: IMO-0042', 'admin_column' => true],
        'preco' => ['type' => 'money', 'label' => 'Preço', 'group' => 'principal', 'min' => 0, 'unit' => '€', 'placeholder' => 'Ex.: 285.000,00', 'help' => 'No arrendamento, indique o valor mensal.', 'admin_column' => true],
        'preco_sob_consulta' => ['type' => 'bool', 'label' => 'Preço sob consulta', 'group' => 'principal', 'help' => 'O preço deixa de aparecer no site.'],
        'classe_energetica' => [
            'type' => 'select', 'label' => 'Classe energética', 'group' => 'principal',
            'options' => ['a+' => 'A+', 'a' => 'A', 'b' => 'B', 'b-' => 'B-', 'c' => 'C', 'd' => 'D', 'e' => 'E', 'f' => 'F', 'isento' => 'Isento', 'em_curso' => 'Em curso'],
            'help' => 'Consta do certificado energético.',
        ],
        'ano_construcao' => ['type' => 'number', 'label' => 'Ano de construção', 'group' => 'principal', 'min' => 1700, 'max' => 2100],
        'destaque' => ['type' => 'bool', 'label' => 'Destacar na página inicial', 'group' => 'principal', 'placement' => 'side', 'admin_column' => true, 'column_label' => 'Destaque'],

        // Áreas e divisões
        'area_util' => ['type' => 'number', 'label' => 'Área útil', 'group' => 'areas', 'min' => 0, 'unit' => 'm²'],
        'area_bruta' => ['type' => 'number', 'label' => 'Área bruta', 'group' => 'areas', 'min' => 0, 'unit' => 'm²'],
        'quartos' => ['type' => 'number', 'label' => 'Quartos', 'group' => 'areas', 'min' => 0, 'max' => 50],
        'wc' => ['type' => 'number', 'label' => 'Casas de banho', 'group' => 'areas', 'min' => 0, 'max' => 50],
        'estacionamento' => [
            'type' => 'select', 'label' => 'Estacionamento', 'group' => 'areas',
            'options' => ['nenhum' => 'Sem estacionamento', 'lugar' => 'Lugar de estacionamento', 'garagem' => 'Garagem', 'box' => 'Box fechada'],
        ],

        // Localização
        'morada' => ['type' => 'text', 'label' => 'Morada', 'group' => 'localizacao', 'public' => false, 'help' => 'Uso interno. Não aparece no site.'],
        'coordenadas' => ['type' => 'geo', 'label' => 'Coordenadas para o mapa', 'group' => 'localizacao', 'help' => 'Latitude e longitude. Pode usar um ponto aproximado se não quiser mostrar a localização exata.'],

        // Fotos e vídeo
        'galeria' => ['type' => 'gallery', 'label' => 'Fotos', 'group' => 'media', 'help' => 'Arraste para ordenar. A primeira foto é a capa.'],
        'planta' => ['type' => 'image', 'label' => 'Planta', 'group' => 'media'],
        'video_url' => ['type' => 'url', 'label' => 'Vídeo ou visita virtual (link)', 'group' => 'media', 'placeholder' => 'https://'],

        // Privado
        'notas_internas' => ['type' => 'textarea', 'label' => 'Notas internas', 'group' => 'privado', 'public' => false, 'help' => 'Só visível no painel. Nunca aparece no site.'],
    ],

    'settings' => [
        'cover_from' => 'galeria',
        'rules' => [
            ['required_unless' => ['preco', 'preco_sob_consulta']],
        ],
        'price_bands' => [
            'field' => 'preco',
            'flag' => 'preco_sob_consulta',
            'taxonomy' => 'faixa',
            'group_taxonomy' => 'finalidade',
            'groups' => [
                'venda' => ['label' => 'Venda', 'suffix' => '€', 'limits' => [100000, 200000, 300000, 500000, 1000000]],
                'arrendamento' => ['label' => 'Arrendamento', 'suffix' => '€/mês', 'limits' => [500, 750, 1000, 1500, 2500]],
            ],
        ],
    ],
];
