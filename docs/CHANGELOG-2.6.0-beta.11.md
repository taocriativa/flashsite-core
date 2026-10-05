# FlashSite Core 2.6.0-beta.11 · Preset Planos

- Novo preset `plano` (Planos e pacotes): preço, "por" (mês/semana/sessão/pacote/ano/único), "desde",
  sob consulta, destaque, etiqueta, frase curta, frequência, duração, "o que inclui" (lista), link do botão,
  modalidade (presencial/online/híbrido/pequeno grupo, editável). Ordem pelo campo "Ordem" do painel.
- Motor: `settings.price` (preço sem faixas), `settings.price_prefix` ("desde "), `price_suffix` por campo.
- Tags: fonte "Item n.º… (pela ordem do painel)" (`item_source: ordered`) e, em "Item · Campo",
  `list_index` para mostrar uma linha de um campo lista.
- Teste PlanoPresetTest. Demo Kit 1.1.0 com 4 planos fictícios.
