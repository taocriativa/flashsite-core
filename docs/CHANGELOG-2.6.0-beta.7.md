# FlashSite Core 2.6.0-beta.7 · Core genérico (não preso ao demo)

## Páginas modelo escolhidas nas Definições da página do Elementor
- Nova secção "FlashSite · Página modelo" (Definições da página › Configurações), guardada em
  `_elementor_page_settings.fs_model_role`. O MCP define-a com `elementor-update-page-settings`.
- Papéis: `site:popup`, `site:404`, `site:header`, `site:footer`, `<preset>:item|card|archive_top|archive_bottom`.
- O título da página deixa de importar (títulos "Modelo · …" continuam a funcionar como alternativa).

## Popups ilimitados
- Qualquer página com papel "Popup" é um popup, com definições próprias:
  `fs_popup_trigger` (delay | exit | scroll), `fs_popup_delay` (s), `fs_popup_scroll` (%),
  `fs_popup_frequency` (days | session | always), `fs_popup_days`, `fs_popup_where`
  (all | home | not_home | collections), `fs_popup_device` (all | desktop | mobile).
- Cada popup tem a sua frequência (deixou de haver "um por visita" global). Nunca abrem dois ao
  mesmo tempo: o seguinte espera que o anterior feche.
- Teste: `?fs_popup=<ID da página>` ou `?fs_popup=delay|exit|scroll`.
- Filtro `flashsite/site_popups` (substitui `flashsite/site_popup_ids` e `flashsite/site_popup_settings`).

## Visual neutro, controlado pelas variáveis do Elementor
- O CSS do Core (filtros, grelha, paginação, popup, páginas de texto) lê variáveis globais com
  nomes fixos: `fs-cor-texto`, `fs-cor-texto-suave`, `fs-cor-destaque`, `fs-cor-fundo`,
  `fs-cor-superficie`, `fs-cor-linha`, `fs-fonte-titulo`, `fs-fonte-texto`. Sem elas, cores neutras.
- Removidas todas as referências às variáveis do demo imobiliário.

## Kit de exemplos fora do Core
- `config/collections/demo-kit/` saiu do plugin. Os kits vêm do plugin à parte "FlashSite Demo Kit"
  (filtro `flashsite/collections/demo_kit_dirs`). Sem ele, a secção de exemplos não aparece.

## Textos da listagem no preset
- `settings.archive_texts` (empty, show_all, any, filter, reset, prev, next), com alternativas
  genéricas e filtro `flashsite/collections/archive_text`.
