# Data Safety Layer 1.5.0

## Política
- desativar não apaga dados
- excluir plugin não apaga dados por padrão
- purga só ocorre com `FLASHSITE_CORE_PURGE_ON_UNINSTALL === true` ou option `flashsite_allow_data_deletion = true`
- cada gravação do perfil gera backup leve do estado anterior
- cada reset do perfil gera backup leve do estado anterior

## Options relevantes
- `flashsite_business_profile`
- `flashsite_business_profile_backup`
- `flashsite_business_profile_backup_meta`
- `flashsite_allow_data_deletion`
