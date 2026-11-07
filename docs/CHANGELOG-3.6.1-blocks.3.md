# 3.6.1-blocks.3 (branch feature/blocks)

Versão renomeada de 3.6.0-blocks.x para 3.6.1-blocks.x: "3.6.0-blocks" é menor que "3.6.0" para o PHP e o update-server ofereceria a 3.6.0 por cima (perdendo os blocos).

- Modelos `single-<coleção>` e `archive-<coleção>` registados para cada coleção ativa, só em temas de blocos (`register_block_template`); o tema pode sobrepor. Link "← <Coleção>" para a lista.
- Bloco `flashsite/item-fields` (ficha do item: selos sim/não + campos públicos preenchidos).
- Pedidos do bloco Formulário guardados em FlashSite › Pedidos do site (CPT privado `fs_pedido`, sem REST nem página pública, só com `flashsite_manage_business_data`), apagados ao fim de 12 meses; o pedido fica guardado mesmo se o e-mail falhar. Só em temas de blocos.
- CSS dos blocos (`blocks.css`) só carrega quando um bloco Flash Site é renderizado (antes ia para todas as páginas dos sites Elementor).
- Testes: grupo `BlocksTest`; 47 grupos a passar.

Compatibilidade com Elementor (07/11/2026, demo-restaurante.flashsite.pt real, Elementor 4.3.6 + Pro 4.3.3):
- HTML de 6 páginas (início, Estilo Carta, Estilo Azulejo, menu, ficha de prato, privacidade) idêntico antes e depois.
- Formulário Elementor: validação de telefone PT e envio com abertura do WhatsApp a funcionar.
- Lighthouse celular: 58 pedidos antes e depois; nota dentro da variação (91–95).
- Menu "Pedidos do site" e modelos de coleção não aparecem (tema clássico).
