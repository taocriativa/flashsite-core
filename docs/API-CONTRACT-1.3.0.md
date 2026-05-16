# FlashSite Core 1.3.0 — API Contract

## Namespace
`/wp-json/flashsite/v1/`

## Public endpoints
- `GET /public/business-profile`
- `GET /public/business-profile/{section}`
- `GET /public/meta`
- `GET /public/output`

## Private endpoints
- `GET /business-profile`
- `GET /business-profile/{section}`

Private endpoints require capability `flashsite_manage_business_data`.

## Visibility policy
- `contact.email_admin` → internal
- `identity.tax_id` → contextual/publicável
- `professional.license` → contextual/publicável
- `professional.secondary_id` → contextual/publicável

## Envelope
The profile endpoints keep backward-compatible top-level keys and also provide `meta`.

```json
{
  "namespace": "flashsite/v1",
  "visibility": "public",
  "section": "",
  "version": "1.3.0",
  "generated_at": "ISO8601",
  "meta": {
    "api_version": "1.3.0",
    "data_version": "1.3.0",
    "generated_at": "ISO8601",
    "visibility": "public",
    "section": ""
  },
  "data": {}
}
```

## Output endpoint
`GET /public/output` returns frontend-oriented values, including formatted phone and resolved branding logo URLs when attachment helpers are available.
