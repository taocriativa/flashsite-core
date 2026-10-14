# FlashSite Core 3.4.0-beta.1 · extensibilidade e primitivos genéricos

Base: 3.3.0. Beta de teste: instalar apenas no demo surf (depois nos outros demos). Não instalar em sites de clientes.

Contrato completo em `COLLECTIONS-CONTRACT.md`.

## Porta de extensão
- **Filtro `flashsite/collections/preset_dirs`:** kits e jobs fornecem pastas de presets. Presets externos têm chave `x_*` obrigatória (prefixo reservado), nunca substituem um oficial, entram desativados e passam pela mesma validação, camada de mercado e isolamento de erro. Colisão de key, post_type ou slug entre presets rejeita o externo.
- **Registro único:** o `MultilingualModule` deixa de carregar os presets por conta própria; usa o registro do container. `CollectionRegistry::fromDirectory()` continua a existir. Novo `fromDirectories()` e `externalDirectories()`.
- **Aviso de slug:** em FlashSite › Coleções, se existe uma página publicada com o endereço da coleção, aparece um aviso antes de ativar (não bloqueia).
- **Filtro `flashsite/i18n/pack_dirs`:** pacotes de tradução de presets externos. O pacote do Core vence em conflito.
- Resíduo `sectors.php` removido do registro.

## Ordenação por campo (`settings.order`, `settings.order_past`)
- Campo público (date, time, number, money, text, select) ou `@date`, `@title`, `@menu_order`; direção; `missing` = `keep` ou `hide`.
- Arquivo, shortcode (`orderby="preset|field:<campo>|@title…"`, tokens antigos intactos), Query ID `flashsite_ordered` e REST (`orderby`, `order`).
- Sem a configuração, a ordenação e os argumentos de consulta são os do 3.3.0.

## Primitiva temporal (`settings.temporal`)
- `when = upcoming | past | all`, com `field`, `end_field`, `undated`, `archive_when`. Nada desaparece por padrão; o passado continua publicado.
- Arquivo (`?when=`), shortcode (`when=`), Query IDs `flashsite_upcoming` e `flashsite_past`, REST (`?when=`). Navegação Próximos · Passados · Todos no arquivo.
- Sem recorrência, reservas, ocorrências ou calendário. Sem cron de cache (decisão da 3.4.0).

## Compatibilidade: contexto do item em arquivos agrupados
- Corrige o contexto de item em arquivos agrupados (`archive_group_by`, ex.: Menu) quando o renderizador externo não substitui o post global durante a renderização do modelo. Cada cartão passa a ter o seu item como post atual (`setup_postdata`) e o post é reposto no fim. Só a renderização agrupada muda.
- O defeito já existia no fluxo do 3.3.0 e foi identificado na regressão da beta.1; a correção tira a dependência implícita do comportamento do renderizador externo com o post global. `GroupedArchiveContextTest` o reproduz também sobre o código do 3.3.0.

## O que não muda
- `imovel`, `plano` e `prato`: nenhum arquivo de preset alterado. O resumo `sha1` de cada preset (PT e BR) é igual ao do 3.3.0 (`tests/fixtures/golden/presets-3.3.0.json`).
- Nenhuma Collection nova ativa por padrão. Efeito conhecido: cada site regrava uma vez as capabilities dos papéis na primeira carga (o hash inclui as chaves do registro).

## Qualidade
- 37 grupos de teste (3 novos: `CollectionExtensibilityTest`, `CollectionOrderTemporalTest`, `GroupedArchiveContextTest`) e `tests/smoke-boot.php`.
- Runner: PHP 8.2.27 do Local (`~/Library/Application Support/Local/lightning-services/php-8.2.27+1/bin/darwin/bin/php tests/run.php`).
- A cláusula de ordenação com itens sem valor (`missing=keep`, `OR` aninhado) precisa de validação num WordPress real (demo surf).
