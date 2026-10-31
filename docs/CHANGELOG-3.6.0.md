# FlashSite Core 3.6.0 · Links ocultos e selo de destaque

Base: 3.5.0. Versão estável que reúne a 3.6.0-beta.1 e a 3.6.0-beta.2, sem mudança funcional em relação à beta.2: só a versão passa a 3.6.0. Validada em produção no site da Milielle (beta.2): 9 páginas públicas com resposta 200, sem erros e sem o link oculto do FULL.Cliente.

## Novo
- **Hardening: links ocultos de terceiros.** O plugin FULL.Cliente (4.1.x, da Full.Services, usado para a licença do Elementor Pro) injeta em todas as páginas públicas um link escondido para `full.services`. O Core retira-o do HTML antes de o enviar.
  - Só sai um `<a>` que aponte para um domínio da lista e esteja escondido por estilo inline (`display:none` ou `visibility:hidden`). Links visíveis e links para outros domínios ficam.
  - Só no site público (fora do painel, AJAX, REST, feeds e robots.txt).
  - Filtros: `flashsite_core_hidden_links` (desliga) e `flashsite_core_hidden_link_domains` (padrão `['full.services']`).
- **Selo de destaque em todas as coleções.** Grupo "Selo de destaque" em cada item: Esgotado, Últimas unidades, Oportunidade, Novidade, Só até uma data ou Outro texto (até 24 caracteres), com data de fim opcional (o selo some sozinho depois do dia).
  - O selo não muda a situação do item.
  - Tag dinâmica **Item · Selo de destaque** (`flashsite-item-badge`), vazia sem selo ou com selo expirado (usar com `fs-esconde-vazio`).
  - Presets podem trocar a lista (`settings.selo_options`), declarar os próprios campos ou desligar (`settings.selo = false`).

## Inalterado
- Licença e funcionamento do FULL.Cliente e do Elementor Pro. Endpoints REST, abilities, coleções, Dynamic Tags existentes, multilíngue. `FLASHSITE_DATA_VERSION` continua 2.6.0. O upgrade não cria conteúdo nem ativa coleções.

## Testes
- 46 grupos ok (inclui `HardeningHiddenLinksTest` e `SeloTest`), `tests/smoke-boot.php`: BOOT OK. PHP 8.2.27.
