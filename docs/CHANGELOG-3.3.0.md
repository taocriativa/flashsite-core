# 3.3.0

## beta.4 (cumulativa: inclui a beta.3)
- **Correção:** salvar os Dados do negócio sem alteração devolvia erro 500. O `update_option()` do WordPress devolve `false` quando o valor já é igual ao gravado, e a API tratava isso como falha. `OptionsStorage::update()` agora relê o valor persistido quando recebe `false` e só mantém `false` se ele continuar diferente do desejado (falha real de gravação). Vale para todas as gravações que passam por `OptionsStorage`.

## Papéis: WooCommerce × Gestor Flash Site (decisão de produto)
- **Nome visível do papel do Core:** "Gestor Flash Site". A chave interna continua `flashsite_site_manager`. O papel `customer` do WooCommerce segue como "Cliente".
- **Separação intencional:** conta WooCommerce (`customer`: compras, pedidos, downloads, faturação, Minha Conta), conta Gestor Flash Site (`flashsite_site_manager`: gestão do site pelo painel) e Administrador (uso técnico). São papéis independentes: o Core não converte, não funde e não atribui papéis automaticamente.
- **Contas separadas recomendadas:** se a mesma pessoa exerce as duas funções, usar duas contas. Acumular `customer` e `flashsite_site_manager` no mesmo utilizador não é fluxo oficial e não tem lógica nem interface própria. Há apenas robustez defensiva: a combinação não escala permissões nem gera erro (o gestor mantém só as capabilities do seu papel).
- **Fluxos oficiais:** `customer` fica na Minha Conta do WooCommerce e nunca acessa o painel do Core. `flashsite_site_manager` vai para "O que pode editar" pelo `/wp-admin/`, pelo login da Minha Conta e ao abrir `/minha-conta/` (exceto `customer-logout`). O Administrador segue no painel normal.

## beta.3
- **WooCommerce × Gestor Flash Site:** quem tem só o papel Gestor Flash Site passa a usar apenas a gestão do Core. O login pela Minha Conta (filtro `woocommerce_login_redirect`) e o acesso autenticado a `/minha-conta/` (`template_redirect`) levam a "O que pode editar". O endpoint `customer-logout` fica livre.
- Quem só tem `customer` segue no WooCommerce.
- O Gestor Flash Site não tem `edit_posts` nem `manage_woocommerce`, então o WooCommerce o expulsava do `/wp-admin/` (e, com o redirecionamento novo, haveria ciclo). O filtro `woocommerce_prevent_admin_access` agora libera o `/wp-admin/` de quem é gestor. Comprador puro continua bloqueado. Nenhuma capability foi adicionada ao papel.
- Os ganchos só agem se o WooCommerce estiver carregado.

## beta.2
- **Compatibilidade com WooCommerce:** o papel `flashsite_site_manager` passa a chamar-se **Gestor Flash Site** (antes aparecia como "Cliente", igual ao papel `customer` do WooCommerce). A chave interna não muda. O nome é aplicado pelo `RoleManager` e corrigido nas instalações existentes (nomes antigos "Gestor de Site" e "Cliente"), sem recriar utilizadores nem mexer em capabilities. O `ClientPanelModule` deixa de forçar o nome.
- O papel `customer` do WooCommerce não é alterado e compradores não recebem acesso de gestão.

## beta.1
- **Correção:** `ModelPages::pageId()` devolvia da cache o id da página modelo sem a tradução do Polylang. Só a primeira chamada de cada pedido era traduzida, por isso a listagem e a página de cada item em EN e FR usavam os modelos do idioma principal. Agora a tradução é resolvida em todas as chamadas.
