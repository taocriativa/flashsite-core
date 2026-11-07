# 3.7.0-beta.7 (feature/3.7.0) · ajustes do teste do editor (07/11/2026)

Teste feito no editor do piloto com sessão real de administrador (temporária, encerrada no fim).

- **Ligações no editor:** as fontes `flashsite/business` e `flashsite/item` passam a ter versão no editor com os valores reais (Dados do Negócio e itens das coleções, até 300), só de leitura. Antes o editor mostrava os textos de exemplo ("0 €", "Resumo"). Campo vazio fica em branco no editor e some no site.
- **Dependências:** Elementor e Elementor Pro (`'stack' => 'elementor'` em config/dependencies.php) deixam de ser pedidos em sites com tema de blocos. Nos sites Elementor nada muda.
- **Secções travadas:** quem não tem `manage_options` (dono, cliente) não pode destravar secções (`canLockBlocks = false`).
- Tema FlashSite PRO e kits: CSS carregado com `enqueue_block_assets` (no site e dentro do editor), para o dono ver igual ao publicado.

Verificado no editor: título e imagem editáveis com a secção travada (sem mover nem apagar); 8 secções prontas com o estilo do site; carta com valores reais; prato editado na ficha do Core refletiu no site, mesmo em cache; Pedidos do site e QR da carta a funcionar.
Por verificar com um utilizador dono real: se o botão "Editar padrão" some com `canLockBlocks = false`.
