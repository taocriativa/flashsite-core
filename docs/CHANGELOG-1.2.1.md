# CHANGELOG — 1.2.1

## Correção
- bloqueada a exposição pública de `contact.email_admin` na camada de output;
- removido o shortcode dedicado `[flashsite_email_admin]`;
- bloqueado o uso de `contact.email_admin` no shortcode genérico `[flashsite_business field="..."]`;
- mantidos `identity.tax_id`, `professional.license` e `professional.secondary_id` como campos publicáveis;
- contrato público da API ajustado para expor apenas o `email_admin` como campo interno.
