# Validation Report 1.5.0

## Objetivo
Validar a camada de segurança de dados após o incidente de duplicação e perda por lifecycle.

## Validado localmente
- uninstall conservador sem purga por padrão
- backup automático antes de gravação
- backup automático antes de reset
- restauro do último backup
- flag persistente `flashsite_allow_data_deletion`
- pacote distribuído com pasta raiz `flashsite-core/`
