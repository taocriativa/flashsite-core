# 3.0.0-beta.1

A versão final da linha 2.6.0 passa a sair como **3.0.0**.

## País do site (Portugal / Brasil)
- FlashSite › Coleções › "País do site": define a terminologia, os campos e as regras das coleções. Sem escolha guardada, é deduzido da moeda e, depois, da língua do WordPress (pt_BR → Brasil). Ao mudar de país, a moeda acompanha se ainda era a do país anterior.
- Os presets têm a base (Portugal) e uma camada `markets.BR` só com o que muda (`MarketLayer`): nomes, termos, campos acrescentados ou retirados, regras e endereço (slug). As chaves dos campos guardados são as mesmas nos dois países.
- **Imobiliário no Brasil:** Venda/Aluguel; Quartos (Studio/kitnet, 1 quarto… 4 quartos ou mais) com endereços próprios (`?fs_imovel_tipologia=2-quartos`); tipos Casa, Casa em condomínio, Cobertura, Sala comercial, Galpão…; situação Alugado; Banheiros, Área total, Suítes, Vagas de garagem, Comodidades, Condomínio (mensal), IPTU (anual), Condomínio ou edifício; sem classe energética nem campo de quartos numérico; faixas de preço em R$ (venda e aluguel); "/mês" no aluguel; "Marcar visita" some em reservado, vendido e alugado.
- **Restaurante no Brasil:** "Cardápio" em /cardapio/, alergênicos (RDC 26/2015 da Anvisa), categorias e textos.
- **Planos no Brasil:** textos e exemplos em R$.
- Tag dinâmica nova **Item · Nome do campo** (`flashsite-item-label`): mostra o nome de um campo ou lista no termo do país (ex.: "Tipologia"/"Quartos", "Casas de banho"/"Banheiros"). Usar nos rótulos das fichas em vez de texto fixo.
- Kits de exemplo por país: o Demo Kit pode trazer `<coleção>-br.php`; sem ele, usa o kit base.

## Notas
- Escolher o país antes de cadastrar conteúdos: os termos das listas são criados para o país escolhido. Mudar de país num site com itens não converte os termos já atribuídos.
