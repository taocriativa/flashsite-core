# FlashSite Core 1.2.1 — API Contract

## Objetivo
Introduzir a primeira camada oficial de acesso programático aos dados do FlashSite Core sem alterar a origem de verdade do plugin.

## Namespace
`flashsite/v1`

## Endpoints

### Público
- `GET /wp-json/flashsite/v1/public/business-profile`
- `GET /wp-json/flashsite/v1/public/business-profile/{section}`

Estes endpoints expõem apenas dados públicos/seguros para consumo frontend e integrações leves.

### Privado
- `GET /wp-json/flashsite/v1/business-profile`
- `GET /wp-json/flashsite/v1/business-profile/{section}`

Estes endpoints exigem a capability `flashsite_manage_business_data`.

## Visibilidade pública
A resposta pública inclui:
- identity: business_name, business_type, tagline
- contact: phone, whatsapp, email_public
- location
- social
- hours
- professional: display_name, title, specialty, services, accepted_plans
- context.segment
- branding

Não expõe por padrão:
- `contact.email_admin`

## Envelope de resposta
```json
{
  "namespace": "flashsite/v1",
  "visibility": "public",
  "section": "",
  "version": "1.2.1",
  "generated_at": "2026-04-14T00:00:00+00:00",
  "data": {}
}
```

## Regras
- leitura apenas nesta fase;
- sem escrita via REST na 1.2.1;
- storage continua interno ao domínio;
- consumidores externos não devem ler `wp_options` diretamente.
