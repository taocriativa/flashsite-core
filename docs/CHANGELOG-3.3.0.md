# 3.3.0

## beta.3
- **WooCommerce × Gestor Flash Site:** quem tem só o papel Gestor Flash Site passa a usar apenas a gestão do Core. O login pela Minha Conta (filtro `woocommerce_login_redirect`) e o acesso autenticado a `/minha-conta/` (`template_redirect`) levam a "O que pode editar". O endpoint `customer-logout` fica livre.
- Quem só tem `customer` segue no WooCommerce. Quem tem `customer` e Gestor Flash Site mantém a Minha Conta no WooCommerce e `/wp-admin/` no painel "O que pode editar".
- O Gestor Flash Site não tem `edit_posts` nem `manage_woocommerce`, então o WooCommerce o expulsava do `/wp-admin/` (e, com o redirecionamento novo, haveria ciclo). O filtro `woocommerce_prevent_admin_access` agora libera o acesso de quem é gestor, com ou sem `customer`. Comprador puro continua bloqueado. Nenhuma capability foi adicionada ao papel.
- Os ganchos só agem se o WooCommerce estiver carregado.

## beta.2
- **Compatibilidade com WooCommerce:** o papel `flashsite_site_manager` passa a chamar-se **Gestor Flash Site** (antes aparecia como "Cliente", igual ao papel `customer` do WooCommerce). A chave interna não muda. O nome é aplicado pelo `RoleManager` e corrigido nas instalações existentes (nomes antigos "Gestor de Site" e "Cliente"), sem recriar utilizadores nem mexer em capabilities. O `ClientPanelModule` deixa de forçar o nome.
- O papel `customer` do WooCommerce não é alterado e compradores não recebem acesso de gestão. Um utilizador com os dois papéis mantém ambos: `/minha-conta/` é do WooCommerce, `/wp-admin/` leva ao painel "O que pode editar".

## beta.1
- **Correção:** `ModelPages::pageId()` devolvia da cache o id da página modelo sem a tradução do Polylang. Só a primeira chamada de cada pedido era traduzida, por isso a listagem e a página de cada item em EN e FR usavam os modelos do idioma principal. Agora a tradução é resolvida em todas as chamadas.
