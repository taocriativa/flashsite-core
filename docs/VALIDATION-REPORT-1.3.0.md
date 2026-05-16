# FlashSite Core 1.3.0 — Validation Report

## Scope validated locally
- Public profile endpoint
- Public section endpoint
- Public metadata endpoint
- Public resolved output endpoint
- Private profile endpoint permission gate
- Public visibility rule for `contact.email_admin`
- Contextual visibility for `tax_id`, `license`, `secondary_id`

## Local result
All local PHP test groups passed using the bundled harness.

## Remaining manual validation in WordPress
- Activate plugin without regressions
- Validate `/wp-json/flashsite/v1/public/meta`
- Validate `/wp-json/flashsite/v1/public/output`
- Confirm `email_admin` stays absent from public API
- Confirm private API still exposes `email_admin` to authorized users
