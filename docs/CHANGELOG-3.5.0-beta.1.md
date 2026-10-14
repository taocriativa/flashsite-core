# FlashSite Core 3.5.0-beta.1 · Abilities para automação e MCP

Base: 3.4.0 (main 7533c7d). Beta de teste: instalar apenas num demo ou staging. Não instalar em sites de clientes. Documentação completa em `ABILITIES.md`.

## Novo
- **Módulo `Abilities`** (`src/Modules/Abilities`): regista a categoria `flashsite` e 13 abilities na Abilities API do WordPress (6.9+): `get-context`, `list-collections`, `get-collection-schema`, `activate-collection`, `deactivate-collection`, `list-items`, `get-item`, `validate-item`, `create-item`, `update-item`, `trash-item`, `get-business-data`, `update-business-data`.
- Sem servidor MCP próprio: o transporte é do plugin oficial WordPress MCP Adapter, instalado à parte e opcional.
- Compatibilidade preservada: o Core continua a exigir WordPress 6.4. Em 6.4 a 6.8 o módulo não faz nada.
- Presets externos (`x_*`) entram em todas as abilities sem código específico.

## Serviços compartilhados (sem mudança de comportamento)
- `BusinessUpdateService`: a gravação dos Dados do Negócio sai do `ApiAccessModule` e passa a ser usada pela REST e pelas abilities. O construtor do módulo é o mesmo.
- `CollectionStateService`: único fluxo de ligar e desligar coleções, usado pelo painel (FlashSite › Coleções) e pelas abilities. O painel passa a gravar o conjunto ativo por ele e dispara a mesma ação `flashsite_collections_changed`.
- `CollectionItemService`: casos de uso de itens sobre o pipeline existente (`ItemSanitizer`, `ItemValidator`, `ItemPersistence`).

## Correção: e-mail inválido não limpa mais o campo
- `BusinessValidator` sanitizava o e-mail antes de validar; no WordPress real um endereço inválido virava `""` e era lido como "limpar o campo" (REST e abilities). Agora, para `email_public` e `email_admin`: omitido preserva, `""` enviado de propósito limpa, válido grava, e valor não vazio e inválido devolve o erro de validação sem gravar nada. Achado no QA real com o MCP Adapter.

## Inalterado
- Todos os endpoints REST. Collections, presets oficiais, `schema_map`, temporal, order, arquivos agrupados, multilíngue, Dynamic Tags e templates. `FLASHSITE_DATA_VERSION` continua 2.6.0. O upgrade não cria conteúdo nem ativa coleções.

## Testes
- 44 grupos (39 anteriores + 5 novos: 4 das Abilities e a validação de e-mail). QA real: WordPress 7.1.3 + MCP Adapter 0.7.0. `tests/smoke-boot.php`: BOOT OK.
- Infraestrutura de testes: stubs de posts e Abilities API em `tests/Support`; `current_user_can` aceita permissão por objeto; `is_wp_error` real; `update_post_meta` aplica o filtro `update_post_metadata`.

## Fora desta beta
- `get-site-texts` e `update-site-texts` (ver `ABILITIES.md`).
