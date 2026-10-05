# FlashSite Core 2.6.0-beta.2

## Novo
- **Moeda dos preços** escolhida em FlashSite › Coleções: Euro, Real, Dólar, Libra, Franco suíço, Kwanza, Metical, Escudo cabo-verdiano. Cada moeda com a sua escrita ("285.000,00 €", "R$ 285.000,00", "$285,000.00").
- A moeda aplica-se aos preços do site, às tags do Elementor, à listagem no painel, às faixas de preço (renomeadas ao mudar de moeda) e ao JSON-LD (`priceCurrency`).
- Leitura dos valores escritos no painel segue o separador decimal da moeda ("285,000.00" em dólar, "285.000,00" em euro/real).

## Melhorias
- Página Coleções mais clara: estado "Ativa/Desligada" ao lado da caixa, aviso quando nenhuma coleção está ativa, mensagem explícita se se guardar sem marcar nenhuma.
