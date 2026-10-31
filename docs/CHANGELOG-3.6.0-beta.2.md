# FlashSite Core 3.6.0-beta.2 · Selo de destaque

Base: 3.6.0-beta.1.

## Novo
- **Selo de destaque em todas as coleções.** Cada item ganha o grupo "Selo de destaque" com três campos:
  - *Selo de destaque*: Esgotado, Últimas unidades, Oportunidade, Novidade, Só até uma data, Outro texto.
  - *Texto do selo*: texto livre (até 24 caracteres), usado em "Outro texto".
  - *Mostrar o selo até*: em "Só até uma data" gera "Só até 30/10"; nos outros, o selo some sozinho depois do dia.
- O selo não muda a situação do item: um imóvel esgotado pode continuar visível.
- Tag dinâmica **Item · Selo de destaque** (`flashsite-item-badge`), vazia quando não há selo ou ele expirou (usar com `fs-esconde-vazio`).
- Presets podem trocar a lista (`settings.selo_options`), declarar os próprios campos ou desligar (`settings.selo = false`).

## Testes
- `SeloTest` novo. `CollectionExtensibilityTest` compara os presets com o 3.3.0 sem os campos do selo.
- 46 grupos ok, BOOT OK (PHP 8.2.27 do Local).
