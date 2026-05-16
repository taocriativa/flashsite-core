# CHANGELOG — 1.2.0

## Added
- módulo `ApiAccessModule` para acesso REST em modo leitura;
- endpoints públicos e privados para `business-profile`;
- contrato inicial da API em `docs/API-CONTRACT-1.2.0.md`;
- testes locais para rotas, permissões e serialização segura.

## Security
- separação entre payload público e payload privado;
- ocultação de campos sensíveis do endpoint público.

## Notes
- esta versão não inclui escrita via REST;
- a fonte de verdade permanece no domínio e em `wp_options`.
