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
        'preco' => ['type' => 'money', 'label' => 'Preço', 'group' => 'principal', 'min' => 0, 'placeholder' => 'Ex.: 285.000,00', 'help' => 'No arrendamento, indique o valor mensal.', 'admin_column' => true],
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
        // Preço no site: "350,00 €/mês" no arrendamento (moeda definida em FlashSite › Coleções).
        'price_suffix' => ['taxonomy' => 'finalidade', 'terms' => ['arrendamento' => '/mês']],
        // Query "flashsite_available" (Loop Grid) e disponibilidade no JSON-LD.
        'unavailable_terms' => ['taxonomy' => 'estado', 'terms' => ['vendido', 'arrendado']],
        // Página do imóvel: sem "Marcar visita" (classe .fs-so-aberto) quando reservado, vendido ou arrendado.
        'closed_terms' => ['taxonomy' => 'estado', 'terms' => ['reservado', 'vendido', 'arrendado']],
        'schema_availability' => [
            'taxonomy' => 'estado',
            'map' => ['disponivel' => 'InStock', 'reservado' => 'LimitedAvailability', 'vendido' => 'SoldOut', 'arrendado' => 'SoldOut'],
        ],
        'featured_field' => 'destaque',
        // Páginas modelo (Elementor, em rascunho) usadas pelo Core em vez do Theme Builder.
        'model_pages' => [
            'item' => 'Modelo · Imóvel',
            'card' => 'Modelo · Cartão de imóvel',
            'archive_top' => 'Modelo · Imóveis topo',
            'archive_bottom' => 'Modelo · Imóveis rodapé',
        ],
        'archive_per_page' => 12,
        'archive_texts' => [
            'empty' => 'Não encontrámos imóveis com estes filtros.',
            'show_all' => 'Ver todos os imóveis',
        ],
        'rules' => [
            ['required_unless' => ['preco', 'preco_sob_consulta']],
        ],
        'price_bands' => [
            'field' => 'preco',
            'flag' => 'preco_sob_consulta',
            'taxonomy' => 'faixa',
            'group_taxonomy' => 'finalidade',
            'groups' => [
                'venda' => ['label' => 'Venda', 'limits' => [100000, 200000, 300000, 500000, 1000000]],
                'arrendamento' => ['label' => 'Arrendamento', 'per' => '/mês', 'limits' => [500, 750, 1000, 1500, 2500]],
            ],
        ],
    ],

    // Brasil: só o que muda (terminologia, campos e regras). As chaves dos campos são as mesmas.
    'markets' => [
        'BR' => [
            'labels' => [
                'title_placeholder' => 'Ex.: Apartamento 2 quartos com varanda na Pituba',
            ],
            'groups' => [
                'areas' => 'Áreas e cômodos',
            ],
            'taxonomies' => [
                'finalidade' => [
                    'terms' => ['venda' => 'Venda', 'aluguel' => 'Aluguel'],
                ],
                'tipo' => [
                    'terms' => [
                        'apartamento' => 'Apartamento',
                        'casa' => 'Casa',
                        'casa-em-condominio' => 'Casa em condomínio',
                        'cobertura' => 'Cobertura',
                        'terreno' => 'Terreno',
                        'sala-comercial' => 'Sala comercial',
                        'loja' => 'Loja',
                        'galpao' => 'Galpão',
                    ],
                ],
                'tipologia' => [
                    'label' => 'Quartos',
                    'singular' => 'Quartos',
                    'terms' => ['studio' => 'Studio / kitnet', '1-quarto' => '1 quarto', '2-quartos' => '2 quartos', '3-quartos' => '3 quartos', '4-quartos' => '4 quartos ou mais'],
                    'help' => 'Não se aplica a terrenos, salas e lojas.',
                ],
                'estado' => [
                    'label' => 'Situação',
                    'terms' => [
                        'disponivel' => 'Disponível',
                        'reservado' => 'Reservado',
                        'vendido' => 'Vendido',
                        'alugado' => 'Alugado',
                    ],
                ],
                'zona' => [
                    'label' => 'Cidades e bairros',
                    'singular' => 'Localização',
                    'help' => 'Cidade e, abaixo dela, o bairro. Pode criar novos.',
                ],
            ],
            'fields' => [
                'referencia' => ['placeholder' => 'Ex.: AP0042'],
                'preco' => ['placeholder' => 'Ex.: 450.000,00', 'help' => 'No aluguel, informe o valor mensal.'],
                'valor_condominio' => ['type' => 'money', 'label' => 'Condomínio (mensal)', 'group' => 'principal', 'min' => 0, 'placeholder' => 'Ex.: 650,00', 'after' => 'preco_sob_consulta'],
                'valor_iptu' => ['type' => 'money', 'label' => 'IPTU (anual)', 'group' => 'principal', 'min' => 0, 'placeholder' => 'Ex.: 1.200,00', 'after' => 'valor_condominio'],
                'classe_energetica' => null,
                'area_bruta' => ['label' => 'Área total'],
                'quartos' => null,
                'suites' => ['type' => 'number', 'label' => 'Suítes', 'group' => 'areas', 'min' => 0, 'max' => 50, 'after' => 'area_bruta'],
                'wc' => ['label' => 'Banheiros'],
                'estacionamento' => null,
                'vagas' => ['type' => 'number', 'label' => 'Vagas de garagem', 'group' => 'areas', 'min' => 0, 'max' => 50, 'after' => 'wc'],
                'comodidades' => [
                    'type' => 'multiselect', 'label' => 'Comodidades', 'group' => 'areas', 'after' => 'vagas',
                    'options' => [
                        'piscina' => 'Piscina',
                        'churrasqueira' => 'Churrasqueira',
                        'area_gourmet' => 'Área gourmet',
                        'academia' => 'Academia',
                        'salao_festas' => 'Salão de festas',
                        'playground' => 'Playground',
                        'portaria_24h' => 'Portaria 24h',
                        'elevador' => 'Elevador',
                        'varanda' => 'Varanda',
                        'ar_condicionado' => 'Ar-condicionado',
                        'armarios' => 'Armários planejados',
                        'mobiliado' => 'Mobiliado',
                        'aceita_pet' => 'Aceita pet',
                    ],
                ],
                'morada' => ['label' => 'Endereço', 'help' => 'Uso interno. Não aparece no site.'],
                'nome_condominio' => ['type' => 'text', 'label' => 'Condomínio ou edifício', 'group' => 'localizacao', 'max_length' => 80, 'placeholder' => 'Ex.: Condomínio Serra Verde', 'after' => 'morada'],
                'video_url' => ['label' => 'Vídeo ou tour virtual (link)'],
            ],
            'settings' => [
                'price_suffix' => ['taxonomy' => 'finalidade', 'terms' => ['aluguel' => '/mês']],
                'unavailable_terms' => ['taxonomy' => 'estado', 'terms' => ['vendido', 'alugado']],
                'closed_terms' => ['taxonomy' => 'estado', 'terms' => ['reservado', 'vendido', 'alugado']],
                'schema_availability' => [
                    'taxonomy' => 'estado',
                    'map' => ['disponivel' => 'InStock', 'reservado' => 'LimitedAvailability', 'vendido' => 'SoldOut', 'alugado' => 'SoldOut'],
                ],
                'archive_texts' => [
                    'empty' => 'Não encontramos imóveis com esses filtros.',
                    'show_all' => 'Ver todos os imóveis',
                ],
                'price_bands' => [
                    'field' => 'preco',
                    'flag' => 'preco_sob_consulta',
                    'taxonomy' => 'faixa',
                    'group_taxonomy' => 'finalidade',
                    'groups' => [
                        'venda' => ['label' => 'Venda', 'limits' => [200000, 400000, 600000, 1000000, 2000000]],
                        'aluguel' => ['label' => 'Aluguel', 'per' => '/mês', 'limits' => [1000, 2000, 3000, 5000, 8000]],
                    ],
                ],
            ],
        ],
    ],
];
