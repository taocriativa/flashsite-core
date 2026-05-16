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
