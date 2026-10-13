# 3.3.0 · versão estável

Reúne as betas 3.3.0-beta.1 a beta.6, validadas no demo surf e no staging do flashsite.pt (WooCommerce). Detalhe em `CHANGELOG-3.3.0-beta.N.md`. Sites com um idioma só e sem WooCommerce não mudam nada.

## Papéis: Gestor Flash Site e WooCommerce
- **Nome visível do papel do Core:** "Gestor Flash Site". A chave interna continua `flashsite_site_manager`. O nome é corrigido nas instalações existentes (antes "Gestor de Site" ou "Cliente"), sem recriar utilizadores nem mexer em capabilities. O papel `customer` do WooCommerce segue como "Cliente" e não é alterado.
- **Separação intencional:** conta WooCommerce (`customer`: compras, pedidos, downloads, faturação, Minha Conta), conta Gestor Flash Site (`flashsite_site_manager`: gestão do site pelo painel) e Administrador. O Core não converte, não funde e não atribui papéis. Se a mesma pessoa exerce as duas funções, a orientação é usar contas separadas. Acumular os dois papéis não é fluxo oficial; a combinação não escala permissões nem gera erro.
- **Fluxos:** `customer` fica na Minha Conta do WooCommerce e nunca acessa o painel do Core. O Gestor Flash Site vai para "O que pode editar" pelo `/wp-admin/`, pelo login da Minha Conta e ao abrir `/minha-conta/` (o `customer-logout` fica livre). O WooCommerce deixa de bloquear o `/wp-admin/` do gestor (filtro `woocommerce_prevent_admin_access`), sem acrescentar capabilities ao papel.

## Dados e gravação
- **Salvar os Dados do negócio sem alteração já não dá erro 500.** O `update_option()` devolve `false` quando o valor é igual ao gravado; `OptionsStorage::update()` relê o valor e só devolve `false` se ele continuar diferente do desejado (falha real de gravação).

## Sites com mais de um idioma
- **Faixas de preço no idioma do visitante** (imobiliário): EN "For sale · Up to €100k", "€100k to €200k", "Over €1M"; FR "Vente · Jusqu'à 100 k€", "100 à 200 k€", "Plus de 1 M€"; ES "Venta · Hasta 100 mil €", "Más de 1 millón €". Português sem alteração. O nome gravado nos termos continua em português; slug, `term_id` e URLs não mudam.
- **Taxonomy Filter nativo do Elementor:** mostra as faixas no idioma da página. A tradução é só de apresentação (`get_terms` e afins, nas taxonomias de faixa), fora do wp-admin, da REST e da gravação. O filtro `flashsite/localize_band_terms` desliga.
- **Horários em texto completo por idioma:** a tag "Horários (Texto Completo)" e o shortcode `[flashsite_hours]` usam os dias e os conectivos do idioma (EN Mon…Sun, "and", "to"; FR Lun…Dim, "et", "à"; ES Lun…Dom, "y", "a") e os valores por dia e a observação de Dados por idioma. A tag agrupa dias seguidos, só se o texto final no idioma for igual; o shortcode segue com um dia por linha. A API pública (`opening_hours`) continua em português.
- **Páginas modelo por idioma:** a tradução do Polylang passa a valer em todas as chamadas de `pageId()`, não só na primeira de cada pedido. Listagem e página de cada item em EN e FR usam os modelos do idioma.

## Qualidade
- 34 grupos de teste, com o português conferido byte a byte contra saídas anteriores nos horários e nas faixas de preço, e teste de carga do plugin (`tests/smoke-boot.php`) antes do ZIP.
