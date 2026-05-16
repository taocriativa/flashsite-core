# FlashSite Core 1.4.0 — Changelog

## Added
- Authenticated write layer for the private REST API.
- Full-profile update endpoint: `POST|PUT|PATCH /wp-json/flashsite/v1/business-profile`.
- Section update endpoint: `POST|PUT|PATCH /wp-json/flashsite/v1/business-profile/{section}`.
- Safe payload mapping from REST JSON to the existing `BusinessValidator` contract.
- Validation and persistence responses for controlled API updates.

## Preserved
- Public API remains read-only.
- `email_admin` remains hidden from all public endpoints.
- Merge-safe persistence remains based on `BusinessValidator` + `BusinessRepository`.

## Fixed / Hardened
- No-write behavior for empty payloads.
- Validation errors now return structured REST-safe responses.
- Partial API updates preserve unrelated sections already saved in the profile.
