# 3.3.0

## beta.2
- **Compatibilidade com WooCommerce:** o papel `flashsite_site_manager` passa a chamar-se **Gestor Flash Site** (antes aparecia como "Cliente", igual ao papel `customer` do WooCommerce). A chave interna não muda. O nome é aplicado pelo `RoleManager` e corrigido nas instalações existentes (nomes antigos "Gestor de Site" e "Cliente"), sem recriar utilizadores nem mexer em capabilities. O `ClientPanelModule` deixa de forçar o nome.
- O papel `customer` do WooCommerce não é alterado e compradores não recebem acesso de gestão. Um utilizador com os dois papéis mantém ambos: `/minha-conta/` é do WooCommerce, `/wp-admin/` leva ao painel "O que pode editar".

## beta.1
- **Correção:** `ModelPages::pageId()` devolvia da cache o id da página modelo sem a tradução do Polylang. Só a primeira chamada de cada pedido era traduzida, por isso a listagem e a página de cada item em EN e FR usavam os modelos do idioma principal. Agora a tradução é resolvida em todas as chamadas.
