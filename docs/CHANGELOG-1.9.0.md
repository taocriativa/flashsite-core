# FlashSite Core — Changelog 1.9.0

## Objetivo
Versão-ponte de transição entre a linha monolítica 1.8.x e o Core consolidado 2.x.

## Alterações principais
- versão atualizada para 1.9.0
- `ManagedHeroModule` removido do carregamento do plugin
- shortcodes e página administrativa do Hero deixam de ser registados pelo Core
- dados legados do Hero em `flashsite_managed_hero` são preservados
- notice administrativo de migração para o futuro plugin FlashSite Design
- helpers públicos iniciais adicionados:
  - `flashsite_core()`
  - `flashsite_core_version()`
  - `flashsite_get_business_data()`
  - `flashsite_get_brand_identity()`

## Mantido
- BusinessData
- AccessControl
- AdminUX
- DependencyManager
- ApiAccess
- SetupWizard
- OutputFoundation

## Observações
- Esta versão deve substituir a 1.8.5.
- Esta versão não remove dados do Hero; apenas remove o carregamento do recurso no Core.
- A linha 2.x só deve entrar após a migração controlada para FlashSite Design.
