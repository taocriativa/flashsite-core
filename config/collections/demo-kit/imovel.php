<?php
declare(strict_types=1);

/**
 * Kit de demonstração · Imóveis fictícios (6).
 *
 * Usado só em sites de demonstração (FlashSite › Coleções › Importar exemplos).
 * Imagens: slugs de anexos já carregados na Biblioteca (ex.: demo-imo-casa-1); se não
 * existirem, o imóvel é criado sem fotos. Itens marcados com _fs_demo_kit para remoção.
 *
 * @since 2.6.0
 */
return [
    'items' => [
        [
            'title' => 'Apartamento T2 com varanda em Alvalade',
            'content' => "Apartamento luminoso num prédio com elevador, a poucos minutos do metro de Alvalade.\n\nSala ampla com saída para a varanda virada a sul, cozinha equipada e dois quartos com roupeiros embutidos. Exemplo fictício para demonstração.",
            'excerpt' => 'T2 com varanda virada a sul, perto do metro de Alvalade.',
            'fields' => ['referencia' => 'MV-0101', 'preco' => '385000', 'classe_energetica' => 'b', 'ano_construcao' => '1998', 'area_util' => '92', 'area_bruta' => '104', 'quartos' => '2', 'wc' => '2', 'estacionamento' => 'lugar', 'destaque' => '1', 'coordenadas' => '38.7530, -9.1440'],
            'terms' => ['finalidade' => 'venda', 'tipo' => 'apartamento', 'tipologia' => 't2', 'estado' => 'disponivel', 'zona' => ['Lisboa', 'Alvalade']],
            'images' => ['demo-imo-casa-1', 'demo-imo-casa-2', 'demo-imo-casa-3'],
        ],
        [
            'title' => 'Moradia T4 com jardim e piscina',
            'content' => "Moradia isolada num lote tranquilo, com jardim, piscina e garagem para dois carros.\n\nQuatro quartos, um deles em suite, e sala com lareira. Exemplo fictício para demonstração.",
            'excerpt' => 'Moradia T4 com jardim, piscina e garagem.',
            'fields' => ['referencia' => 'MV-0102', 'preco' => '1250000', 'classe_energetica' => 'a', 'ano_construcao' => '2015', 'area_util' => '245', 'area_bruta' => '310', 'quartos' => '4', 'wc' => '3', 'estacionamento' => 'garagem', 'destaque' => '1', 'coordenadas' => '38.7020, -9.4210'],
            'terms' => ['finalidade' => 'venda', 'tipo' => 'moradia', 'tipologia' => 't4', 'estado' => 'disponivel', 'zona' => ['Cascais', 'Alcabideche']],
            'images' => ['demo-imo-casa-2', 'demo-imo-casa-4', 'demo-imo-casa-1'],
        ],
        [
            'title' => 'Apartamento T3 junto ao rio',
            'content' => "Apartamento com vista parcial de rio, em condomínio com zona verde e parque infantil.\n\nCozinha aberta para a sala e três quartos. Exemplo fictício para demonstração.",
            'excerpt' => 'T3 em condomínio com vista parcial de rio.',
            'fields' => ['referencia' => 'MV-0103', 'preco' => '520000', 'classe_energetica' => 'a+', 'ano_construcao' => '2021', 'area_util' => '128', 'area_bruta' => '140', 'quartos' => '3', 'wc' => '2', 'estacionamento' => 'box', 'destaque' => '1', 'coordenadas' => '38.6960, -9.3110'],
            'terms' => ['finalidade' => 'venda', 'tipo' => 'apartamento', 'tipologia' => 't3', 'estado' => 'reservado', 'zona' => ['Oeiras', 'Paço de Arcos']],
            'images' => ['demo-imo-casa-3', 'demo-imo-casa-1'],
        ],
        [
            'title' => 'Estúdio T0 renovado no centro',
            'content' => "Estúdio totalmente renovado, pronto a habitar, numa rua calma do centro histórico.\n\nIdeal para primeira casa ou investimento. Exemplo fictício para demonstração.",
            'excerpt' => 'T0 renovado, pronto a habitar.',
            'fields' => ['referencia' => 'MV-0104', 'preco' => '1150', 'classe_energetica' => 'c', 'ano_construcao' => '1950', 'area_util' => '38', 'quartos' => '0', 'wc' => '1', 'estacionamento' => 'nenhum', 'coordenadas' => '38.7130, -9.1370'],
            'terms' => ['finalidade' => 'arrendamento', 'tipo' => 'apartamento', 'tipologia' => 't0', 'estado' => 'disponivel', 'zona' => ['Lisboa', 'Santa Maria Maior']],
            'images' => ['demo-imo-casa-4', 'demo-imo-casa-3'],
        ],
        [
            'title' => 'Apartamento T1 com terraço',
            'content' => "T1 com terraço de 20 m², arrendado recentemente.\n\nMantém-se na listagem como referência de imóvel já arrendado. Exemplo fictício para demonstração.",
            'excerpt' => 'T1 com terraço, já arrendado.',
            'fields' => ['referencia' => 'MV-0105', 'preco' => '1400', 'classe_energetica' => 'b-', 'ano_construcao' => '2008', 'area_util' => '61', 'quartos' => '1', 'wc' => '1', 'estacionamento' => 'lugar'],
            'terms' => ['finalidade' => 'arrendamento', 'tipo' => 'apartamento', 'tipologia' => 't1', 'estado' => 'arrendado', 'zona' => ['Cascais', 'Estoril']],
            'images' => ['demo-imo-casa-1', 'demo-imo-casa-4'],
        ],
        [
            'title' => 'Loja com montra em zona comercial',
            'content' => "Loja ampla com montra de rua e casa de banho, numa zona com muito movimento.\n\nPreço sob consulta. Exemplo fictício para demonstração.",
            'excerpt' => 'Loja com montra de rua, preço sob consulta.',
            'fields' => ['referencia' => 'MV-0106', 'preco_sob_consulta' => '1', 'classe_energetica' => 'd', 'area_util' => '85', 'wc' => '1', 'estacionamento' => 'nenhum'],
            'terms' => ['finalidade' => 'venda', 'tipo' => 'loja', 'estado' => 'disponivel', 'zona' => ['Oeiras', 'Algés']],
            'images' => ['demo-imo-casa-3'],
        ],
    ],
];
