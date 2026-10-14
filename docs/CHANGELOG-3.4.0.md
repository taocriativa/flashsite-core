# 3.4.0 · versão estável

Reúne as betas 3.4.0-beta.1 e beta.2, validadas no demo surf, nos demos restaurante, personal e imobiliário e no staging do flashsite.pt. Detalhe em `CHANGELOG-3.4.0-beta.N.md`; contrato em `COLLECTIONS-CONTRACT.md`. **Nenhuma Collection nova fica ativa por padrão:** um site que não as ative não muda nada.

## Extensibilidade das Collections
- **Presets externos:** o filtro `flashsite/collections/preset_dirs` permite que kits e jobs forneçam pastas de presets. A chave começa por `x_` (prefixo reservado), o preset entra desativado, nunca substitui um oficial e passa pela mesma validação, camada de mercado e isolamento de erro. Colisão de key, post_type ou slug rejeita o externo.
- **Registro único:** o `MultilingualModule` deixa de carregar os presets por conta própria e usa o registro do container. `CollectionRegistry::fromDirectory()` continua a existir.
- **Traduções externas:** filtro `flashsite/i18n/pack_dirs`. O pacote do Core vence em conflito.
- **Aviso de slug:** em FlashSite › Coleções, se existe uma página publicada com o endereço da coleção, o painel avisa antes de ativar (não bloqueia).
- Promoção de um preset externo ao Core não tem alias automático: é uma tarefa explícita com plano de migração.

## Ordenação e janela temporal
- **`settings.order` e `settings.order_past`:** ordenação por campo público (date, time, number, money, text, select) ou `@date`, `@title`, `@menu_order`. Arquivo, shortcode (`orderby="preset|field:<campo>"`), Query ID `flashsite_ordered` e REST (`orderby`, `order`). Sem a configuração, a ordenação é a de sempre.
- **`settings.temporal`:** `when = upcoming | past | all`, com `field`, `end_field`, `undated` e `archive_when`. Arquivo (`?when=`), shortcode, Query IDs `flashsite_upcoming` e `flashsite_past` e REST. O passado continua publicado; nada some por padrão.
- Sem recorrência, reservas, calendário nem cron de cache. Quem usa cache de página deve considerar um TTL curto nas listas de eventos.

## Onda 1 do catálogo
- **`servico`** (`/servicos/`): serviços e ofertas profissionais, com preço opcional ("desde" e "sob consulta" funcionam por campo). Distinto de `plano`, que continua como era.
- **`equipa`** (`/equipa/`, `/equipe/` no Brasil): várias pessoas de uma organização. Foto = imagem de capa; biografia = editor.
- **`projeto`** (`/projetos/`): portfólio, casos e obras. Capa pela primeira foto da galeria.
- **`evento`** (`/eventos/`): próximos e passados pela primitiva temporal, estado fixo (confirmado, esgotado, adiado, cancelado), regra `data_fim >= data_inicio`. Evento passado, adiado ou cancelado continua publicado.
- Textos dos quatro em EN, ES e FR; camada BR para `equipe`, "Registro profissional" e "Contatos".

## `schema_map`
- Mapeia propriedades Schema.org para campos públicos do preset (texto, data, número) ou para o valor fixo de um termo, sem condicionais por preset. Opcional; sem ele o JSON-LD é igual ao do 3.3.0.
- **Limitações conhecidas:** `Event.location` é texto simples (não `Place`); `startDate` e `endDate` levam só a data; a equipa não gera `sameAs` múltiplo; "Gratuito" não é escrito pelo Core (o modelo decide). Estruturas aninhadas e listas ficam no kit do job.

## Compatibilidade
- **`imovel`, `plano` e `prato` não mudaram:** nenhum arquivo de preset alterado; resumos `sha1` dos presets (PT e BR) e JSON-LD iguais ao 3.3.0, conferidos por teste.
- **Arquivos agrupados (`archive_group_by`, ex.: Menu):** corrige o contexto de item quando o renderizador externo não substitui o post global durante a renderização do modelo. O defeito já existia no fluxo do 3.3.0 e foi identificado na regressão da beta.1.
- Cada site regrava uma vez as capabilities dos papéis na primeira carga (o hash inclui as chaves do registro). Sem perda de dados.

## Qualidade
- 39 grupos de teste (inclui `GroupedArchiveContextTest`, `CollectionExtensibilityTest`, `CollectionOrderTemporalTest`, `OndaUmPresetsTest` e `SchemaMapTest`) e teste de carga do plugin (`tests/smoke-boot.php`, BOOT OK) antes do ZIP.
- Regressão em WordPress real: demos restaurante, surf, personal trainer e imobiliário sem regressões; preset `evento` validado no staging do flashsite.pt.
