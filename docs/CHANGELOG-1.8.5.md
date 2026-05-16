# FlashSite Core 1.8.5 — Changelog

## Correções
- Hero banners agora são enfileirados explicitamente no preview/editor do Elementor.
- Adicionado reforço por `MutationObserver` no JS do Hero para inicialização após inserções dinâmicas no editor.
- Mantida a abordagem de markup limpo, sem CSS/JS inline no shortcode.

## Impacto
- Corrige o cenário em que o shortcode funciona no frontend/WordPress clássico, mas no Elementor editor aparece empilhado ou vazio.
