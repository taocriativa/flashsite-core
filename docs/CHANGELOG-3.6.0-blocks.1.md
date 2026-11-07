# 3.6.0-blocks.1 (piloto, branch feature/blocks)

Base: v3.6.0. Só para o piloto `demo-restaurante-blocos.flashsite.pt` (JOB_FLS_26-11_piloto-blocos). Nunca no flashsite.pt.

Novo módulo `Blocks` (src/Modules/Blocks), ponte do Core com o editor de blocos:
- Ligações de dados `flashsite/business` (args `field` = caminho dos Dados do Negócio; `link` = phone|whatsapp|email|maps) e `flashsite/item` (args `field` = campo do preset, `price`, `title`, `terms:<tax>`; `label` para campos sim/não).
- Bloco ligado ao Core com valor vazio não é impresso (filtro render_block).
- Blocos: `flashsite/form` + `flashsite/form-field` (envio em POST /wp-json/flashsite/v1/form; campos lidos do bloco gravado; isco, 3 s mínimos, 8 envios/hora por IP; e-mail para ContactModule::recipientEmail(); resumo no WhatsApp opcional; ação `flashsite_form_submitted`), `flashsite/hours` (agrupa dias seguidos), `flashsite/map` ([flashsite_maps]).
- Editor em JS simples (assets/blocks/editor.js), sem compilação.

Testado no piloto (07/11/2026): ligações, Query Loop de fs_prato com preço e selos, horário, envio válido/inválido/rápido/form inexistente. Por testar: editor visual com sessão de administrador.
