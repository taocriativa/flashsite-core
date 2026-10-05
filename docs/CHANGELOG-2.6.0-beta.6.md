# FlashSite Core 2.6.0-beta.6

## Novo: popups do site sem Elementor Pro Popups
- Página "Modelo · Popup" (rascunho, Elementor) aparece em todas as páginas aos 30 s de navegação (tempo somado entre páginas na mesma visita).
- Página opcional "Modelo · Popup saída" aparece quando o rato sai pelo topo da janela (só em computador). Sem ela, a saída usa "Modelo · Popup".
- No máximo um popup por visita. Depois de fechado (X, Esc, clique fora ou botão com link `#fechar`) não volta durante 7 dias; editar o modelo reinicia a contagem.
- Não aparece no editor, nas pré-visualizações nem nas próprias páginas "Modelo · …".
- Teste: acrescentar `?fs_popup=timer` ou `?fs_popup=exit` a qualquer URL.
- Filtros: `flashsite/site_popup_settings` (delay, cooldown) e `flashsite/site_popup_ids` ([] desliga).
- Ficheiros: assets/frontend/js/fsc-popup.js, assets/frontend/css/fsc-popup.css.
