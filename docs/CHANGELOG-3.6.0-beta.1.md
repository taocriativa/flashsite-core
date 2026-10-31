# FlashSite Core 3.6.0-beta.1 · Limpeza de links ocultos de terceiros

Base: 3.5.0 (main fbae834). Beta de teste: instalar apenas num demo ou staging. Não instalar em sites de clientes nem no flashsite.pt.

## Novo
- **Hardening: links ocultos de terceiros.** O plugin FULL.Cliente (4.1.x, da Full.Services, usado para a licença do Elementor Pro) injeta no `wp_footer` de todas as páginas públicas um link escondido: `<a href="https://full.services/" style="… display: none;">plugins premium WordPress</a>`. O visitante não o vê; o Google sim. O Core passa a retirá-lo do HTML antes de o enviar.
- Regra: só sai um `<a>` que aponte para um domínio da lista **e** esteja escondido por estilo inline (`display:none` ou `visibility:hidden`). Links visíveis para o mesmo domínio ficam. Links ocultos para outros domínios ficam.
- Só no site público: fora do painel, AJAX, REST, feeds e robots.txt. O buffer abre no fim do `template_redirect`, por isso apanha o rodapé.
- Filtros: `flashsite_core_hidden_links` (desliga tudo) e `flashsite_core_hidden_link_domains` (padrão `['full.services']`; acrescentar outros injetores).

## Inalterado
- Licença e funcionamento do FULL.Cliente e do Elementor Pro. Endpoints REST, coleções, Dynamic Tags, `FLASHSITE_DATA_VERSION` 2.6.0.

## Testes
- Novo grupo `HardeningHiddenLinksTest` (caso real do FULL.Cliente, variantes, link visível, outro domínio, domínio parecido, lista vazia).
- A regex foi verificada contra o HTML real de demo-lancamento.flashsite.pt: sai 1 de 14 links (149 caracteres), o resto do HTML fica igual.
- `php tests/run.php` não correu nesta máquina (sem PHP). Correr antes da estável.
