# 3.1.0-beta.1

- **Baixa de preço (Imobiliário):** campos novos na ficha, "Preço anterior" e "Mostrar baixa de preço no site" (Portugal: "Mostrar redução de preço no site").
  - Quando o preço muda (painel, REST ou importação), o valor antigo fica guardado sozinho como preço anterior. Pode ser corrigido à mão.
  - Só aparece no site quando a opção está ligada e o preço anterior é maior que o atual. Subidas nunca aparecem: ao subir, a opção desliga-se.
  - Preço "sob consulta" esconde a baixa.
  - Tag nova **Item · Baixa de preço** (`flashsite-item-price-drop`): selo "Preço reduzido" (texto editável) ou o preço anterior ("R$ 780.000"). Fica vazia quando não há baixa: com a classe `fs-esconde-vazio` o bloco some.
  - Configuração genérica no preset: `settings.price_drop` (`field`, `previous`, `show`).
- Testes: PriceDropTest (24 grupos).
- Pedido do primeiro site real no Brasil (Milielle Oliveira): imóvel CA1150 baixou de R$ 780.000 para R$ 720.000.
