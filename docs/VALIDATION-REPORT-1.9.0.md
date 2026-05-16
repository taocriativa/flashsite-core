# FlashSite Core — Validation Report 1.9.0

## Escopo validado
- slug do plugin mantido como `flashsite-core`
- cabeçalho do plugin atualizado para 1.9.0
- constantes internas alinhadas com 1.9.0
- módulo `ManagedHeroModule` removido de `config/modules.php`
- bindings e referências principais do Hero removidos de `Application.php`
- option legada `flashsite_managed_hero` preservada
- notice administrativo de migração adicionado
- helpers públicos mínimos adicionados

## Resultado esperado de instalação
- WordPress deve reconhecer a 1.9.0 como atualização da 1.8.5
- atualização não deve apagar dados do negócio nem dados legados do Hero
- Hero deixa de renderizar pelo Core
- site continua a usar os demais módulos do Core normalmente

## Pendências intencionais
- migração funcional do Hero para FlashSite Design
- redução final de branding para identidade mínima
- limpeza final de cores/fontes no Core 2.x
