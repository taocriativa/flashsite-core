# 2.6.0-beta.23

- Módulo novo **Segurança básica** (`HardeningModule`), ativo em todos os sites, sem painel:
  - cabeçalhos no site público: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (câmara, microfone, localização e pagamento desligados) e `Strict-Transport-Security` (1 ano, só em HTTPS, sem subdomínios);
  - `/wp-json/wp/v2/users` e `?author=N` fechados a visitantes (utilizadores com sessão ou senha de aplicação, como o MCP, continuam a ter acesso);
  - XML-RPC desligado e cabeçalho `X-Pingback` removido;
  - `/.well-known/security.txt` com contacto `mailto:suporte@flashsite.pt` e validade de 1 ano.
- Cada item desliga-se por filtro: `flashsite_core_hardening_headers`, `flashsite_core_hardening_users`, `flashsite_core_hardening_xmlrpc`, `flashsite_core_security_txt`; contacto em `flashsite_core_security_contact`.
