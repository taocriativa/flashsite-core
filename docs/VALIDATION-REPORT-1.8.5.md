# FlashSite Core 1.8.5 — Validation Report

## Diagnóstico aplicado
Os sintomas reportados no Elementor editor indicam que o HTML do shortcode está a ser inserido, mas os assets do Hero não estão garantidamente carregados no iframe/preview do editor quando a renderização acontece via AJAX. O resultado é empilhamento de slides ou área vazia.

## Ajustes feitos
- Enqueue explícito do CSS/JS do Hero no preview e editor do Elementor.
- Inicialização resiliente com `MutationObserver` para novos nós inseridos dinamicamente.

## Resultado esperado
- Hero visível no editor do Elementor em páginas novas e existentes.
- Slides não empilhados sem CSS.
- Carrossel inicializado mesmo após re-render dinâmico.
