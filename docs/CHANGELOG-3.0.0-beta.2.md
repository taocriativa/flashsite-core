# 3.0.0-beta.2

- **Venda e aluguel no mesmo imóvel (Brasil):** a Finalidade aceita várias opções. Campo novo "Valor do aluguel (mensal)" (`preco_aluguel`), usado só quando o imóvel tem Venda e Aluguel; o preço principal fica como valor de venda. No site: "R$ 350.000,00 · R$ 3.500,00/mês". O imóvel recebe uma faixa de preço por finalidade, por isso aparece nos filtros de venda e de aluguel.
- **Lançamentos (Brasil):** finalidade "Lançamento", preço com "a partir de", faixas próprias e grupo de campos Lançamento (Construtora, Previsão de entrega, Plantas). Os Quartos aceitam várias opções, para as plantas do empreendimento.
- Genérico, para qualquer coleção: `settings.extra_price` (segundo preço ligado a um termo) e `settings.price_prefix` por termo (`['taxonomy' => …, 'terms' => [termo => 'texto ']]`).
