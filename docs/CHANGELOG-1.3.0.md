# FlashSite Core 1.3.0 — Changelog

## Added
- Public API metadata endpoint: `/public/meta`
- Public resolved output endpoint: `/public/output`
- Dedicated API serialization layer (`PublicSerializer`, `MetaProvider`, `OutputResolver`)
- Extended local test coverage for API consolidation

## Changed
- Public profile endpoints now include a `meta` block while preserving prior top-level keys
- API visibility rules are now explicit and documented

## Preserved
- Read-only API approach
- Private endpoint capability gate
- `email_admin` remains blocked from public API/output
