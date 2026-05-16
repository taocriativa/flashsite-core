# FlashSite Core 1.5.0

## Tema
Data Safety Layer, packaging com slug fixo e proteções de lifecycle.

## Alterações principais
- slug de empacotamento fixado para `flashsite-core/` no pacote de distribuição
- uninstall continua conservador e agora também respeita a option `flashsite_allow_data_deletion`
- backup leve automático antes de cada save/reset do `BusinessProfile`
- restauro do último backup via repositório e página de segurança de dados
- nova página admin `Segurança de Dados` com política de remoção, backup manual e restauro
- guard rail administrativo para instalações com pasta incorreta do plugin
