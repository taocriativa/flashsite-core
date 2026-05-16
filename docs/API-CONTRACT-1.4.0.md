# FlashSite Core 1.4.0 — API Contract

## Public read endpoints
- `GET /wp-json/flashsite/v1/public/meta`
- `GET /wp-json/flashsite/v1/public/output`
- `GET /wp-json/flashsite/v1/public/business-profile`
- `GET /wp-json/flashsite/v1/public/business-profile/{section}`

## Private read endpoints
- `GET /wp-json/flashsite/v1/business-profile`
- `GET /wp-json/flashsite/v1/business-profile/{section}`

## Private write endpoints
- `POST|PUT|PATCH /wp-json/flashsite/v1/business-profile`
- `POST|PUT|PATCH /wp-json/flashsite/v1/business-profile/{section}`

Private endpoints require an authenticated user with `flashsite_manage_business_data`.

## Safe write rules
- Only private endpoints support writes.
- Payloads may be sent as nested JSON under `data` or as direct section objects.
- Section updates are partial and preserve unrelated saved data.
- Validation is delegated to the existing `BusinessValidator`.
- Invalid payloads return `validation_failed` with status `422`.
- Empty write payloads return `empty_payload` with status `400`.

## Example — full profile partial write
```json
{
  "data": {
    "contact": {
      "phone": "+351 966 111 222",
      "email_public": "novo@flashsite.pt"
    },
    "branding": {
      "primary_color": "#112233"
    }
  }
}
```

## Example — section write
`PATCH /wp-json/flashsite/v1/business-profile/contact`

```json
{
  "phone": "+351 966 111 222",
  "email_public": "novo@flashsite.pt"
}
```
