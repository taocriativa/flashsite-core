<?php
declare(strict_types=1);

/**
 * Preset Menu · prato
 *
 * Restaurantes, cafés, pastelarias e bares. Arquivo /menu/ (agrupado por categoria),
 * página individual /menu/<slug>/. A ordem dentro de cada categoria é a do campo "Ordem".
 * Alergénios: os 14 de declaração obrigatória na UE (Regulamento (UE) n.º 1169/2011).
 *
 * @since 2.6.0
 */
return [
    'key' => 'prato',
    'post_type' => 'fs_prato',
    'slug' => 'menu',
    'labels' => [
        'singular' => 'Prato',
        'plural' => 'Menu',
        'add_new' => 'Adicionar prato',
        'add_new_item' => 'Adicionar prato',
        'edit_item' => 'Editar prato',
        'new_item' => 'Novo prato',
        'not_found' => 'Ainda não há pratos no menu.',
        'all_items' => 'Todos os pratos',
        'title_placeholder' => 'Ex.: Polvo à lagareiro',
        'editor_label' => 'Descrição do prato',
    ],
    'menu_icon' => 'dashicons-food',
    'schema' => 'MenuItem',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields', 'page-attributes'],

    'groups' => [
        'principal' => 'Prato',
        'alergenios' => 'Alergénios e dieta',
        'privado' => 'Notas internas',
    ],

    'taxonomies' => [
        'categoria' => [
            'label' => 'Categorias do menu',
            'singular' => 'Categoria',
            'single' => true,
            'required' => true,
            'default' => 'pratos',
            'terms' => [
                'entradas' => 'Entradas',
                'pratos' => 'Pratos',
                'sobremesas' => 'Sobremesas',
                'bebidas' => 'Bebidas',
            ],
            'locked' => false,
            'help' => 'Pode criar outras categorias (ex.: Petiscos, Vinhos).',
            'admin_column' => true,
        ],
    ],

    'fields' => [
        'preco' => ['type' => 'money', 'label' => 'Preço', 'group' => 'principal', 'min' => 0, 'placeholder' => 'Ex.: 16,50', 'admin_column' => true],
        'preco_sob_consulta' => ['type' => 'bool', 'label' => 'Preço sob consulta', 'group' => 'principal', 'help' => 'Ex.: peixe ao quilo.'],
        'resumo' => ['type' => 'text', 'label' => 'Descrição curta', 'group' => 'principal', 'max_length' => 140, 'placeholder' => 'Ex.: Polvo assado, batata a murro, alho e azeite.'],
        'porcao' => ['type' => 'text', 'label' => 'Porção', 'group' => 'principal', 'max_length' => 40, 'placeholder' => 'Ex.: Para 2 pessoas'],
        'destaque' => ['type' => 'bool', 'label' => 'Destacar na página inicial', 'group' => 'principal', 'placement' => 'side', 'admin_column' => true, 'column_label' => 'Destaque'],
        'etiqueta' => ['type' => 'text', 'label' => 'Etiqueta', 'group' => 'principal', 'placement' => 'side', 'max_length' => 30, 'placeholder' => 'Ex.: Sugestão do chef'],
        'esgotado' => ['type' => 'bool', 'label' => 'Esgotado hoje', 'group' => 'principal', 'placement' => 'side', 'help' => 'Sai do site até desligar. Não apaga o prato.', 'admin_column' => true, 'column_label' => 'Esgotado'],

        'alergenios' => [
            'type' => 'multiselect', 'label' => 'Alergénios', 'group' => 'alergenios',
            'options' => [
                'gluten' => 'Glúten',
                'crustaceos' => 'Crustáceos',
                'ovos' => 'Ovos',
                'peixe' => 'Peixe',
                'amendoins' => 'Amendoins',
                'soja' => 'Soja',
                'leite' => 'Leite',
                'frutos_casca_rija' => 'Frutos de casca rija',
                'aipo' => 'Aipo',
                'mostarda' => 'Mostarda',
                'sesamo' => 'Sésamo',
                'sulfitos' => 'Sulfitos',
                'tremoco' => 'Tremoço',
                'moluscos' => 'Moluscos',
            ],
            'help' => 'Os 14 alergénios de declaração obrigatória na UE.',
        ],
        'vegetariano' => ['type' => 'bool', 'label' => 'Vegetariano', 'group' => 'alergenios'],
        'vegano' => ['type' => 'bool', 'label' => 'Vegano', 'group' => 'alergenios'],
        'sem_gluten' => ['type' => 'bool', 'label' => 'Sem glúten', 'group' => 'alergenios'],

        'notas_internas' => ['type' => 'textarea', 'label' => 'Notas internas', 'group' => 'privado', 'public' => false, 'help' => 'Só visível no painel. Nunca aparece no site.'],
    ],

    'settings' => [
        'price' => ['field' => 'preco', 'flag' => 'preco_sob_consulta'],
        'featured_field' => 'destaque',
        'hide_field' => 'esgotado',
        'archive_group_by' => 'categoria',
        'archive_per_page' => -1,
        'archive_filters' => false,
        'archive_columns' => 2,
        'model_pages' => [
            'item' => 'Modelo · Prato',
            'card' => 'Modelo · Cartão de prato',
            'archive_top' => 'Modelo · Menu topo',
            'archive_bottom' => 'Modelo · Menu rodapé',
        ],
        'archive_texts' => [
            'empty' => 'O menu está a ser atualizado.',
            'show_all' => 'Ver o menu',
        ],
        'rules' => [
            ['required_unless' => ['preco', 'preco_sob_consulta']],
        ],
    ],
];
