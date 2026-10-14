# FlashSite Abilities (3.5.0-beta.1)

Camada oficial de ações estruturadas do FlashSite Core para automação e MCP.

## Arquitetura

```
Cliente AI
   ↓
MCP
   ↓
WordPress MCP Adapter oficial   (plugin independente, opcional)
   ↓
WordPress Abilities API         (WordPress 6.9+)
   ↓
FlashSite Abilities             (src/Modules/Abilities)
   ↓
Serviços de domínio do Core     (CollectionItemService, CollectionStateService,
                                 BusinessUpdateService, ItemPersistence, ...)
```

- O Core **não implementa MCP**: sem servidor, transporte, JSON-RPC, sessões, autenticação ou endpoint próprio. Isso é do MCP Adapter.
- O Core só **regista Abilities** (`wp_register_ability_category()` e `wp_register_ability()`). Elas existem com ou sem o Adapter.
- As Abilities são adaptadores finos. Nunca chamam o REST do próprio site: chamam PHP/domínio.
- REST, painel e Abilities terminam na mesma regra:

```
REST ─┐
Admin ├─→ mesma regra de domínio
Ability ┘
```

## Compatibilidade

| Ambiente | Comportamento |
|---|---|
| WordPress 6.4 a 6.8 | Core normal. Os hooks da Abilities API nunca disparam: o módulo fica inativo. Sem notice, sem polyfill. |
| WordPress 6.9+ | Categoria `flashsite` e 13 abilities registadas. |
| MCP | Exige WordPress 6.9+ e o plugin MCP Adapter instalado à parte. Opcional. |

O requisito global do Core continua `Requires at least: 6.4`. O Core não declara `Requires Plugins: mcp-adapter` e não mostra aviso por falta do Adapter.

## Exposição pública

Cada ability declara `meta.public = true` (flag geral do WordPress 7.1) e `meta.mcp.public = true` (versões do Adapter anteriores à flag geral; quando as duas existem, a do canal prevalece e tem o mesmo valor). "Pública" significa visível a clientes externos, **não** execução anónima: toda ability tem `permission_callback`.

`meta.annotations` indica `readonly`, `destructive` e `idempotent` para o cliente decidir com cautela.

## Ability Pack v1

Namespace `flashsite/`. Os campos dinâmicos de Collections vêm do preset (Registry), nunca estão fixos no módulo: presets externos `x_*` funcionam sem código específico.

| Ability | Tipo | Capability |
|---|---|---|
| `flashsite/get-context` | leitura | qualquer capability FlashSite (gerir coleções, gerir negócio ou editar itens de uma coleção) |
| `flashsite/list-collections` | leitura | idem |
| `flashsite/get-collection-schema` | leitura | idem |
| `flashsite/activate-collection` | escrita, idempotente | `flashsite_manage_collections` |
| `flashsite/deactivate-collection` | escrita, idempotente | `flashsite_manage_collections` |
| `flashsite/list-items` | leitura | `edit_<coleção>_items` (ex.: `edit_fs_imovel_items`) |
| `flashsite/get-item` | leitura | idem, e `edit_post` no item |
| `flashsite/validate-item` | leitura (sem gravar) | idem |
| `flashsite/create-item` | escrita | idem; publicar exige `publish_<coleção>_items` |
| `flashsite/update-item` | escrita (patch) | idem, e `edit_post` no item; publicar exige `publish_<coleção>_items` |
| `flashsite/trash-item` | escrita (Lixo) | idem, e `delete_post` no item |
| `flashsite/get-business-data` | leitura | `flashsite_manage_business_data` |
| `flashsite/update-business-data` | escrita (patch) | `flashsite_manage_business_data` e `InstallationGuard` |

### Exemplos conceituais

Descobrir o contrato antes de gravar:

```json
// flashsite/get-collection-schema
{ "collection": "imovel" }
// → fields[{key:"preco", type:"money", required:false, ...}], taxonomies[...], settings{price_drop,...}
```

Criar um rascunho:

```json
// flashsite/create-item
{
  "collection": "imovel",
  "title": "T2 em Alvalade",
  "fields": { "referencia": "IMO-1", "preco": "285.000,00" },
  "taxonomies": { "finalidade": "venda", "tipo": "apartamento", "zona": "Alvalade" }
}
// → { "item": { "id": 123, "status": "draft", ... }, "created": true, "incomplete": {} }
```

Patch (o que não for enviado é preservado, incluindo booleanos):

```json
// flashsite/update-item
{ "collection": "imovel", "id": 123, "fields": { "preco": 250000 } }
// → { "changed_fields": ["preco"], "item": { "fields": { "preco_anterior": 285000, ... } } }
```

Erro de validação (nada é gravado):

```json
{ "code": "validation_failed", "data": { "status": 422,
  "errors": { "prec0": { "code": "unknown_field", "message": "Campo desconhecido: \"prec0\"." } } } }
```

### Regras de gravação de itens

- Estado por defeito: `draft`. Só publica com `status=publish` explícito, capability de publicar e ficha íntegra. Nunca publica implicitamente.
- Campo desconhecido, valor inválido (que o sanitizador descartaria em silêncio), termo desconhecido ou taxonomia automática rejeitam o pedido inteiro, sem gravar nada.
- Rascunho incompleto (campo obrigatório em falta) é gravado e devolvido em `incomplete`, como no painel.
- Um item publicado não pode ser tornado inválido por uma ability.
- Termos: slug, nome ou ID. Termos novos só em taxonomias não fixas (um por pedido). Faixa de preço e outras taxonomias `auto` são calculadas pelo Core.
- Preço anterior (baixa de preço), faixa de preço e capa (`cover_from`) são aplicados pelo pipeline do Core (`ItemPersistence` e o filtro `update_post_metadata`).
- Um ID de outro tipo de conteúdo responde `item_not_found`, igual a "não existe".
- `trash-item` usa o Lixo do WordPress. Não existe exclusão permanente.
- Paginação: `per_page` máximo 50.
- Multilíngue: o comportamento atual é preservado. `lang` em `list-items` só funciona com Polylang. Sem criação automática de traduções nesta versão.

### Ativar e desativar coleções

Passam por `CollectionStateService`, o mesmo serviço usado pelo painel FlashSite › Coleções. Dispara `flashsite_collections_changed` (sincroniza capabilities, agenda semente de termos fixos e rewrite). Idempotente: se já estava no estado pedido, não faz nada. Desativar nunca apaga itens. Mercado, moeda e idiomas não são alterados. A coleção fica utilizável a partir do pedido seguinte.

### Dados do Negócio

Leitura sobre `BusinessData` (a mesma visão da REST privada). Escrita sobre `BusinessUpdateService`, extraído do `ApiAccessModule` sem mudar comportamento: `InstallationGuard`, achatamento de payload por secção, validação, persistência e log. Uma ability não consegue gravar o que a REST bloquearia (`installation_protected`, 409).

## Segurança

- Toda ability tem `permission_callback` com capabilities reais do Core e do WordPress. Nada de `manage_options`.
- Autenticação, credenciais e Application Passwords são do WordPress e do MCP Adapter. O Core não guarda nada disso. Recomendação operacional: um utilizador WordPress dedicado, com o mínimo de capabilities (por exemplo, o papel Gestor Flash Site para itens).
- Itens: o ID é sempre confirmado contra o post type da coleção. As capabilities por item (`edit_post`, `delete_post`) são refeitas com o ID real.
- `get-context` não devolve credenciais, caminhos, salts nem segredos.

### O que deliberadamente não é exposto

Executar PHP, SQL, shell ou WP-CLI; sistema de ficheiros; instalar plugins ou temas; editar código; utilizadores e papéis; options e meta arbitrários; credenciais; update-server; GitHub Releases; internos do Elementor; exclusão permanente de itens.

## Separação de domínios

| Camada | Domínio |
|---|---|
| FlashSite Abilities | Collections, itens e Dados do Negócio |
| MCP do Elementor | páginas, containers, widgets, classes, variables, componentes |
| Chrome | QA visual |
| REST atual | compatibilidade e operações ainda não migradas (nada foi removido ou depreciado) |

O Core não depende de nenhum MCP de Elementor.

## Fora desta beta

- `flashsite/get-site-texts` e `flashsite/update-site-texts`: o `SiteTextsRegistry` mistura descoberta por página do Elementor, textos por idioma e limites de comprimento. Expor com segurança pede uma extração de serviço própria. Fica para a próxima fase.
- Criação e associação de traduções, delete permanente, painel MCP, tokens, OAuth, auditoria.

## QA com o MCP Adapter oficial (staging ou demo)

Pré-requisitos: WordPress 6.9+ (idealmente o mesmo que vai a produção), plugin **WordPress MCP Adapter** oficial instalado e ativo à parte, FlashSite Core 3.5.0-beta.1 (apenas num demo, nunca num site de cliente), e dois utilizadores: um **Administrador** e um utilizador de teste com papel **Gestor Flash Site** (sem `flashsite_manage_collections`). Cada um com uma Application Password.

1. **Descoberta.** `mcp-adapter/discover-abilities` lista as 13 abilities `flashsite/*` (e só elas, mais as do WordPress/Adapter).
2. **Schema.** `mcp-adapter/get-ability-info` com `flashsite/create-item` devolve o input_schema (enum de coleções, `status` com default `draft`).
3. **Leitura.** `mcp-adapter/execute-ability` com `flashsite/get-context` e `flashsite/list-collections` responde com o mercado e as coleções do demo.
4. **Negação.** Com o utilizador Gestor: `flashsite/activate-collection` falha por permissão; `flashsite/get-business-data` falha.
5. **Escrita controlada.** Com o Administrador, numa coleção já ativa do demo: `create-item` em `draft` com poucos campos, depois `update-item` (patch) e `get-item`.
6. **WordPress.** O item aparece em Painel › coleção, como rascunho, com os campos na ficha, a faixa de preço e a capa calculadas.
7. **Rejeição.** `update-item` com um campo inventado devolve `validation_failed`/`unknown_field` e não altera nada.
8. **Limpeza.** `trash-item` no item de teste. Confirmar que ficou no Lixo (não apagado).
9. **Frontend.** O arquivo e as páginas das coleções continuam corretos (o rascunho não aparece). Verificar visualmente no Chrome.
10. **Sem Adapter.** Desativar o Adapter: o site funciona igual e o painel não mostra avisos.

Não executar operações destrutivas em produção durante o QA.
