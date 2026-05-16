# FlashSite Core — Output Contract

## Princípio
O output consome o domínio centralizado do FlashSite Core. Nenhum builder é fonte primária de dados.

## Shortcodes principais
- `[flashsite_business_name]` → `identity.business_name`
- `[flashsite_phone]` → `contact.phone`
- `[flashsite_whatsapp]` → `contact.whatsapp.number`
- `[flashsite_whatsapp_link]` → `contact.whatsapp.link`
- `[flashsite_email]` → `contact.email_public`
- `[flashsite_address]` → `location.address`
- `[flashsite_website]` → `social.website_url`
- `[flashsite_tagline]` → `identity.tagline`
- `[flashsite_logo_light]` → `branding.logo_light_id`
- `[flashsite_logo_dark]` → `branding.logo_dark_id`

## Atributos genéricos
### Texto
- `fallback`
- `before`
- `after`
- `multiline=yes|no`

### Imagem
- `size`
- `class`
- `alt`
- `fallback`
- `link=none|file|attachment`
- `width`
- `height`

## Regras
- valores vazios usam fallback quando fornecido;
- output textual é escapado;
- output multiline aplica `nl2br` apenas quando solicitado;
- output de imagem só renderiza quando há attachment resolvido.

## Política de privacidade de output
- `contact.email_admin` é campo interno e não deve ser exposto por shortcode público;
- o shortcode dedicado de email administrativo não é registado na linha 1.2.1;
- o shortcode genérico também bloqueia `field=contact.email_admin`;
- NIF, registo profissional e ID secundário continuam publicáveis quando necessários.
