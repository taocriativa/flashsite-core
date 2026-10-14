# FlashSite Core — Architecture Overview

## Objetivo
O FlashSite Core centraliza dados do negócio e expõe esses dados para consumo administrativo e frontend, mantendo o domínio desacoplado de builders específicos.

## Camadas
- `Core`: bootstrap, container, módulos, assets, schema manager e logger
- `Domain`: perfil do negócio, onboarding, validação e acesso
- `Infrastructure`: persistência em `wp_options`
- `Modules`: UI administrativa, wizard, dependências e output

## Fonte de verdade
O option key `flashsite_business_profile` é a fonte única do domínio de negócio.
Elementor e shortcodes são consumidores; não são a fonte de verdade.

## Output
### Shortcodes
Os shortcodes leem dados via `BusinessData` e permitem output sem dependência de Elementor.

### Elementor
As Dynamic Tags ficam isoladas em `src/Modules/OutputFoundation/Elementor` e resolvem dados de forma lazy, sem acoplamento por construtor ao ciclo interno do Elementor.

## Versionamento
- `FLASHSITE_CORE_VERSION`: versão do plugin
- `FLASHSITE_DATA_VERSION`: versão do schema/dados

## Uninstall
Por padrão, o uninstall é conservador e não remove dados.
Para purga deliberada, usar `FLASHSITE_CORE_PURGE_ON_UNINSTALL`.

## Collections (3.4)
O módulo `Collections` é um motor genérico de conteúdos repetíveis. Cada tipo é um preset declarativo (`config/collections/<key>.php`); não existe código por preset. Fluxo:

`config/collections` (Core) + pastas do filtro `flashsite/collections/preset_dirs` (kits, chaves `x_*`) -> `CollectionRegistry` (um só por requisição, no container) -> `CollectionsModule` (só presets ativos: CPT, taxonomias, post meta, capabilities) -> Gestor (`ItemEditor`), REST pública, Dynamic Tags, páginas modelo (`ModelPages`/`ModelRoles`), Polylang.

Os presets oficiais incluem a Onda 1 (`servico`, `equipa`, `projeto`, `evento`, desativados por padrão). O JSON-LD de cada item vem de `SchemaOrgBuilder`; `settings.schema_map` (`SchemaMap`) acrescenta propriedades simples declaradas pelo preset.

Consultas de lista passam por `CollectionQuery`, que junta `OrderResolver` (`settings.order`) e `TemporalResolver` (`settings.temporal`). Sem essas configurações o comportamento é o do Core 3.3.0.

Contrato do preset, do preset externo, ordenação, primitiva temporal e governança (Core × Kit de setor × Kit do job × Business): `COLLECTIONS-CONTRACT.md`.
