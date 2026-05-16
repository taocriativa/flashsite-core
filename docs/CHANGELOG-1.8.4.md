# FlashSite Core 1.8.4 — Hero Hardening

## Correções principais
- extração do CSS do Hero para `assets/frontend/css/fsc-hero.css`
- extração do JS do Hero para `assets/frontend/js/fsc-hero.js`
- remoção de `<style>` e `<script>` inline no shortcode `[flashsite_hero_banners]`
- inicialização idempotente do slider via `window.FlashSiteHero`
- compatibilidade reforçada com re-render do Elementor via `frontend/element_ready/global`
- enfileiramento dedicado de assets frontend do Hero
- alinhamento de versionamento interno para `1.8.4`

## Impacto esperado
- redução de falhas de renderização do Hero no editor do Elementor
- menor risco de conflito em múltiplas instâncias do shortcode
- comportamento mais previsível entre páginas diferentes e re-renderizações AJAX
