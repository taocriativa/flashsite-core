# FlashSite Core 1.2.1 — Validation Report

## Objetivo
Corrigir a incoerência entre política pública da API e camada pública de output, bloqueando apenas `email_admin`.

## Validações locais
- rotas REST públicas continuam disponíveis;
- API pública mantém `tax_id`, `license` e `secondary_id`;
- API pública não expõe `email_admin`;
- shortcode dedicado de email administrativo não é registado;
- shortcode genérico bloqueia `contact.email_admin`;
- shortcodes de campos publicáveis continuam funcionais.
