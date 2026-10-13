# 3.3.0-beta.3

- **WooCommerce × Gestor Flash Site:** quem tem só o papel Gestor Flash Site passa a usar apenas a gestão do Core. O login pela Minha Conta (filtro `woocommerce_login_redirect`) e o acesso autenticado a `/minha-conta/` (`template_redirect`) levam a "O que pode editar". O endpoint `customer-logout` fica livre.
- Quem só tem `customer` segue no WooCommerce.
- O Gestor Flash Site não tem `edit_posts` nem `manage_woocommerce`, então o WooCommerce o expulsava do `/wp-admin/` (e, com o redirecionamento novo, haveria ciclo). O filtro `woocommerce_prevent_admin_access` agora libera o `/wp-admin/` de quem é gestor. Comprador puro continua bloqueado. Nenhuma capability foi adicionada ao papel.
- Os ganchos só agem se o WooCommerce estiver carregado.
