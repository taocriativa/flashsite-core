# Contrato das Collections · FlashSite Core 3.4

Documento de referência para quem cria, estende ou promove Collections. Vale a partir da 3.4.0-beta.1. Itens marcados **(beta.2)** entram na segunda beta.

## 1. O que é uma Collection

Um preset declarativo (um arquivo PHP que devolve um array) que o motor transforma em tipo de conteúdo: CPT `fs_<key>`, taxonomias, campos em post meta, ficha no painel, REST pública, Dynamic Tags, páginas modelo do Elementor, JSON-LD e suporte a Polylang. O motor é genérico: não existe código por preset. Tudo o que muda de um preset para outro está no arquivo.

Presets oficiais: `imovel`, `plano`, `prato` (e, na beta.2, `servico`, `equipa`, `projeto`, `evento`). Ficam em `config/collections/`.

## 2. Estrutura do preset

Chaves: `key`, `post_type`, `slug`, `labels`, `groups`, `taxonomies`, `fields`, `supports`, `schema`, `menu_icon`, `settings`, `markets`.

- `key`: snake_case ASCII, 2 a 17 caracteres. Vira post_type (`fs_<key>`, máximo 20), nome de capability, papel de página modelo (`<key>:item|card|archive_top|archive_bottom`), chave em `per_language` e em `flashsite_collections_active`. **Chaves são dados: nunca se renomeiam.**
- Campos: 18 tipos (`FieldType`). Público grava em `fs_<campo>`; privado em `_fs_<campo>` (nunca vai à REST nem ao site).
- `markets`: camada por país (`PT` é a base; `BR` altera só o que muda).

### settings

| Chave | Efeito |
|---|---|
| `price`, `price_prefix`, `price_suffix`, `price_drop`, `price_bands`, `extra_price` | preço e variações |
| `featured_field`, `hide_field`, `unavailable_terms`, `closed_terms`, `schema_availability` | destaque e disponibilidade |
| `cover_from`, `rules` | capa pela galeria; regras de validação |
| `model_pages`, `archive_*` | páginas modelo e listagem |
| **`order`, `order_past`** | ordenação por campo (3.4.0, seção 4) |
| **`temporal`** | janela próximos/passados/todos (3.4.0, seção 5) |
| **`schema_map` (beta.2)** | propriedades schema.org mapeadas a campos |

### Convenções de nomes (contrato implícito)

O motor assume estes nomes quando o preset não diz outra coisa. Documentadas aqui em vez de refatoradas, para não mexer nos presets atuais:

| Convenção | Usada por |
|---|---|
| campo `preco` (e `preco_sob_consulta`, `preco_anterior`) | tags de preço, shortcode `[flashsite_item field="preco"]`, `orderby="price"`, JSON-LD (Offer), faixas |
| campo `destaque` (ou `settings.featured_field`) | Query ID `flashsite_featured`, REST `featured=1`, shortcode `featured="1"` |
| `area_util`, taxonomias `estado` e `zona` | tags de atalho do imobiliário (as tags genéricas "Item · Campo" e "Item · Classificação" servem qualquer preset) |

## 3. Preset externo (kit, job, extensão)

### Contrato

1. **Onde:** uma pasta com arquivos `<nome>.php`, cada um no formato da seção 2.
2. **Registro:** o plugin do kit liga o filtro **no carregamento do arquivo do plugin** (antes de `plugins_loaded`, prioridade 10, quando o Core inicializa):

```php
add_filter('flashsite/collections/preset_dirs', static function (array $dirs): array {
    $dirs[] = __DIR__ . '/presets';
    return $dirs;
});
```

3. **Chave:** obrigatoriamente `x_<nome>` (3 a 17 caracteres). O prefixo `x_` é reservado: nenhum preset oficial o usa. O post_type fica `fs_x_<nome>`.
4. **Regras do registro:**
   - preset externo nunca substitui um oficial (o Core é lido primeiro);
   - chave sem `x_`, colisão de key, post_type ou slug com outro preset: **rejeitado**, com o erro no log e em FlashSite › Coleções;
   - a mesma validação (`CollectionPreset`), a mesma camada de mercado (`MarketLayer`) e o mesmo isolamento de erro dos presets oficiais;
   - entra **desativado**; o Administrador ativa em FlashSite › Coleções, como os oficiais.
5. **Slug igual ao de uma página publicada:** o painel mostra um **aviso** antes de ativar ("Existe uma página publicada utilizando este slug. A ativação desta Collection pode alterar qual conteúdo responde nesta URL."). Não bloqueia, não renomeia, não redireciona.
6. **Traduções:** filtro `flashsite/i18n/pack_dirs` com uma pasta que tenha `<idioma>.php` no formato `['phrases' => ['texto em português' => 'translation']]`. Só acrescenta; em conflito o pacote do Core vence.
7. **Itens de exemplo:** o filtro já existente `flashsite/collections/demo_kit_dirs`.
8. **Se o plugin do kit for desativado:** o preset sai do registro e o CPT deixa de ser registado. Os itens ficam na base; nada é apagado.

### Hooks

| Hook | Tipo | Contrato |
|---|---|---|
| `flashsite/collections/preset_dirs` | filter | recebe `[]`; cada kit acrescenta pastas. A pasta do Core é sempre carregada primeiro e não aparece na lista. Valores inválidos, inexistentes e duplicados são descartados. |
| `flashsite/i18n/pack_dirs` | filter | recebe `[]`; cada kit acrescenta pastas com `<idioma>.php`. |
| `flashsite/collections/demo_kit_dirs` | filter | (já existia) pastas com `<key>.php` de itens de exemplo. |

Não há API estática de registro: o filtro de pastas mantém os arquivos declarativos, a validação e o isolamento de erros.

### Promoção de um preset externo ao Core

Um `x_*` promovido a oficial **não** é migrado automaticamente. Não existe alias de chave. A promoção é uma tarefa explícita, com plano de migração de dados (post_type, capabilities, papéis de página modelo, opções) escrito e aprovado antes. Um preset externo pertence ao kit do job até essa decisão; usar a porta oficial não o torna parte do produto padrão.

## 4. Ordenação (`settings.order`)

```php
'order' => [
    ['by' => 'data_inicio', 'dir' => 'ASC', 'missing' => 'keep'],
    ['by' => '@title',      'dir' => 'ASC'],
],
'order_past' => [['by' => 'data_inicio', 'dir' => 'DESC']],   // opcional
```

- `by`: chave de campo público de tipo date, time, number, money, text ou select; ou `@date`, `@title`, `@menu_order`. Campo privado, galeria, geo, lista e chaves inexistentes são ignorados (ordenar por campo privado revelaria o valor).
- `dir`: `ASC` (padrão) ou `DESC`.
- `missing`: `keep` (padrão) mantém os itens sem valor, onde o MySQL os coloca (valores nulos primeiro em ASC e por último em DESC); `hide` tira da lista os itens sem valor. Não há `first/last`: exigiria SQL próprio em `posts_clauses`, que o Core evita.
- Desempate final sempre `menu_order ASC, data DESC` (a ordem de sempre das Collections).
- **Sem inversão automática.** `order_past` é opcional e só vale com `when=past`; sem ele vale `order`.
- **Sem `settings.order`, nada muda**: os presets atuais mantêm exatamente a ordenação e os argumentos de consulta do Core 3.3.0.

| Superfície | Como usar |
|---|---|
| Arquivo | automático, pelo preset |
| Shortcode | `orderby="preset"` ou `orderby="field:<campo>"`, `@title`, `@date`, `@menu_order`; `order="ASC\|DESC"`. Os valores antigos (`date`, `title`, `menu_order`, `rand`, `price`) continuam iguais. |
| Loop Grid › Query ID | `flashsite_ordered` (novo). `flashsite_featured` e `flashsite_available` não mudam. |
| REST | `orderby=preset\|@date\|@title\|@menu_order\|field:<campo>`, `order=ASC\|DESC`. Sem estes parâmetros a resposta é a de sempre. |

## 5. Primitiva temporal (`settings.temporal`)

```php
'temporal' => [
    'field' => 'data_inicio',   // campo date (obrigatório)
    'end_field' => 'data_fim',  // campo date (opcional)
    'undated' => 'upcoming',    // upcoming (padrão) | past | none
    'archive_when' => 'upcoming', // when padrão do arquivo (all se omitido)
],
```

- **Contrato técnico:** `when = upcoming | past | all`. Independente de idioma e de mercado. A interface pode mostrar "Próximos / Passados / Todos" traduzidos; a URL e a API usam sempre os três valores acima.
- **Nada desaparece por padrão.** Sem pedido de `when`, a lista traz tudo. O item passado continua publicado e acessível.
- **Referência do item:** `end_field` se o item o tiver; senão `field`. `upcoming` = referência >= hoje; `past` = referência < hoje (o dia de hoje é "próximo"); `all` = sem filtro.
- **Hoje:** dia atual no fuso do WordPress. Os campos date são dias de calendário; não há conversão de fuso nem hora na comparação.
- **Item sem data:** `undated` decide em qual janela aparece (`none` = só em `all`). Em `all` aparece sempre.
- **Só quando pedido.** Ao contrário de `hide_field`, a primitiva não age em consultas que não a pediram (nem no Loop nativo do Elementor 4 sem Query ID).

| Superfície | Como usar |
|---|---|
| Arquivo | `/eventos/?when=past`; sem parâmetro vale `archive_when`. A navegação "Próximos · Passados · Todos" aparece sozinha nos presets com `temporal`. |
| Shortcode | `when="upcoming"` |
| Loop Grid › Query ID | `flashsite_upcoming`, `flashsite_past` |
| REST | `?when=upcoming`; sem parâmetro, `all` |

**Fora do escopo, de propósito:** recorrência, reservas, geração de ocorrências, calendário, hora com fuso (não há tipo datetime).

**Cache de página:** listas "próximos" mudam à meia-noite. O Core não purga cache de terceiros nem agenda tarefas para isso (decisão da 3.4.0). Quem usa cache de página deve considerar um TTL curto nas páginas de eventos. Comportamento a validar nos ambientes Flash Site.

## 6. Governança

### Core × Kit de setor × Kit do job × Business

| Camada | O que é | Onde vive |
|---|---|---|
| **Core / Collections** | estrutura genérica de dados, reutilizável por vários setores | `flashsite-core` (`config/collections/`) |
| **Kit de setor** | combinação de Collections + templates Elementor + textos + configuração para um segmento | plugin de kit, fora do Core (ex.: Kit Saúde = Serviço + Equipa + FAQ, se existirem) |
| **Kit do job** | necessidade específica de um cliente (campos, regras, visual) | `CLIENTES_FLASH/<Cliente>/_JOBS/<JOB>/_# Producao/Dev/<cliente>-kit/` |
| **Business** | personalização comercial paga | kit do job |

- Necessidade de um único cliente vai para o kit do job / Business. O kit pode registrar um preset `x_*` pela porta oficial, **sem** que isso o torne parte do produto padrão.
- Kit de setor pode ser criado quando houver decisão comercial de atender o segmento e as Collections necessárias já forem estáveis. Não exige "dois sites reais".

### Fluxo para cada nova necessidade

Classificar sempre: **A** Core existente · **B** adaptação ao padrão (preferência) · **C** kit do job / Business · **D** possível evolução do Core.

1. O Core já atende? (A)
2. O cliente pode se adaptar ao padrão? (B)
3. É específico do cliente ou personalização paga? (C)
4. Parece transversal? Avaliar os critérios abaixo. (D)
5. Registrar a proposta de produto (problema, setores, campos, comportamento, classe) e decidir antes de implementar. Nada de implementação automática.

### Critérios para uma Collection entrar no Core

Todos os obrigatórios e pelo menos 3 dos desejáveis. Cliente real é evidência adicional, não requisito.

Obrigatórios: recorrência entre setores; valor comercial; necessidade de CRUD pelo cliente; ganho real de ser Collection; não duplica WordPress nem WooCommerce; complexidade proporcional (cabe no vocabulário de campos/settings ou exige um primitivo genérico, nunca de um só preset); acompanha fixture de teste, itens de exemplo e traduções EN/ES/FR.

Desejáveis: página própria por item (SEO); filtros/taxonomias; REST e Dynamic Tags; schema.org natural; menos trabalho repetido nos jobs.

### Critérios de exclusão

Específico de um cliente ou profissão; venda de produto (WooCommerce); poucos itens que quase não mudam (seção fixa resolve); exige condicional de um só preset no motor; uma categoria de Post resolve sem perda.

### Ficha de custo (por preset novo)

Antes de aprovar: traduções em 3 idiomas, itens de exemplo, fixture e testes, modelos Elementor de demonstração, documentação.

## 7. Serviço × Plano **(beta.2)**

- **Plano** (`plano`): pacote, modalidade comercial, recorrência, periodicidade, preço estruturado. Continua como é.
- **Serviço** (`servico`): atividade ou oferta profissional; preço opcional; funciona sem preço.
- Não se unificam, não se renomeiam e não se migram nesta versão.
