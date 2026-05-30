# FlashSite Core 2.5.0

## Tags Elementor — tradução completa para português

Todos os títulos de dynamic tags foram traduzidos para português do Brasil. Lista completa:

| Anterior (EN) | Novo (PT) |
|---|---|
| Business Name | Nome do Negócio |
| Business Type | Tipo de Negócio |
| Tagline | Tagline / Slogan |
| Tax ID | NIF / CNPJ |
| Phone | Telefone |
| Phone Link | Telefone (Link tel:) |
| WhatsApp | WhatsApp (Número) |
| WhatsApp Link | WhatsApp (Link) |
| Email | E-mail Público |
| Email Link | E-mail (Link mailto:) |
| Admin Email | E-mail Administrativo |
| Address | Endereço |
| City / Region | Cidade / Região |
| Postal Code | Código Postal / CEP |
| Country | País |
| Full Address | Endereço Completo |
| Opening Hours | Horários (Texto Completo) |
| Website | Website |
| Google Maps URL | Google Maps (Link) |
| Logo Light | Logo Clara |
| Logo Dark | Logo Escura |
| Primary Color | Cor Principal |
| Secondary Color | Cor Secundária |
| Accent Color | Cor de Destaque |

---

## Novas dynamic tags — Horários individuais por dia

8 novas tags Elementor, uma por dia da semana + observação:

| Tag | Campo |
|---|---|
| FlashSite: Horário — Segunda-feira | hours.monday |
| FlashSite: Horário — Terça-feira | hours.tuesday |
| FlashSite: Horário — Quarta-feira | hours.wednesday |
| FlashSite: Horário — Quinta-feira | hours.thursday |
| FlashSite: Horário — Sexta-feira | hours.friday |
| FlashSite: Horário — Sábado | hours.saturday |
| FlashSite: Horário — Domingo | hours.sunday |
| FlashSite: Horário — Observação | hours.notes |

Cada tag retorna o texto do horário daquele dia de forma independente, permitindo layout personalizado no Elementor.

---

## Nova página admin — Short codes

Nova entrada no menu lateral: **Short codes**.

Lista completa de todos os shortcodes disponíveis organizados por grupo (Identificação, Contacto, Localização, Horários, Redes Sociais, Dados Profissionais, Branding, Política de Privacidade), com botão **Copiar** em cada linha.

O bloco "Output Foundation" foi removido da página "Dados do Negócio" e substituído por um link direto para a nova página de shortcodes.

---

## Novo módulo — Política de Privacidade

Nova entrada no menu lateral: **Política de Privacidade**.

- Editor TinyMCE nativo do WordPress (bold, itálico, listas, links)
- Auto-datação: a cada save, a data de atualização é registada automaticamente
- Shortcode `[flashsite_privacy_policy]` — renderiza o conteúdo com `wpautop` + `wp_kses_post`
- Shortcode `[flashsite_privacy_date]` — data da última atualização (formato padrão d/m/Y)
- Shortcode `[flashsite_privacy_date format="Y-m-d"]` — formato personalizado via PHP date()

Dados guardados em `wp_options` sob chaves próprias (`flashsite_privacy_policy_content`, `flashsite_privacy_policy_updated`). Zero impacto no BusinessProfile existente.

---

## Compatibilidade

- Nenhuma breaking change. Todos os shortcodes e tags das versões anteriores mantêm comportamento idêntico.
- `FLASHSITE_CORE_VERSION` e header `Version:` em sync em `2.5.0`.
