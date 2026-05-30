# FlashSite Core 2.4.0

## Correções críticas

### Compatibilidade Elementor — Dynamic Tags não apareciam no editor

**Causa:** `BaseBusinessTag` estendia diretamente `\Elementor\Core\DynamicTags\Data_Tag`. No Elementor ≥ 3.21, essa classe foi removida. O guard em `registerElementorTags` verificava apenas `Data_Tag` — se ausente, nenhuma tag era registada.

**Correção:**
- Novo ficheiro `ElementorTagBase.php`: define `ElementorTagBase` condicionalmente — estende `Data_Tag` se disponível, caso contrário estende `Tag`.
- `BaseBusinessTag` agora estende `ElementorTagBase` em vez de `Data_Tag` diretamente.
- Guard em `registerElementorTags` expandido: aceita `Data_Tag` OU `Tag` como base válida.

Resultado: todas as 30+ dynamic tags voltam a aparecer no editor Elementor independente da versão instalada.

---

## Novas funcionalidades

### Shortcode `[flashsite_hours_list]`

Renderiza os horários de funcionamento como `<ul><li>` estruturado, com `<span>` separados para dia e hora.

```html
<ul class="flashsite-hours-list">
  <li>
    <span class="flashsite-dia">Segunda-feira</span>
    <span class="flashsite-hora">08h às 18h</span>
  </li>
  ...
  <li class="flashsite-hours-obs"><em>Consultas com agendamento prévio.</em></li>
</ul>
```

Atributos:
- `class` — classe da `<ul>` (default: `flashsite-hours-list`)
- `obs_class` — classe do `<li>` de observação (default: `flashsite-hours-obs`)

Substitui `[flashsite_hours]` em contextos onde o output precisa de estrutura HTML (Elementor HTML widget, rodapé, página de contacto).

---

### Shortcode `[flashsite_maps]`

Renderiza o embed Google Maps armazenado em `location.google_maps_embed` como `<iframe>` seguro via `wp_kses`.

```
[flashsite_maps]
```

Segurança: apenas iframes com `google.com/maps` no `src` são permitidos. Equivalente ao `[tao_maps]` do TAO Studio: Dados do Site.

---

### Shortcode `[flashsite_accepted_plans]`

Renderiza os planos/convênios aceites (`professional.accepted_plans`) como lista `<ul><li>`.

```
[flashsite_accepted_plans class="minha-classe"]
```

Aceita valores em array (Setup Wizard) ou texto com um item por linha.

---

### Shortcodes de campos profissionais

| Shortcode | Campo | Uso |
|---|---|---|
| `[flashsite_prof_display_name]` | `professional.display_name` | Nome curto para CTAs e headings |
| `[flashsite_prof_title]` | `professional.title` | Cargo/título (ex: Oftalmologista) |
| `[flashsite_prof_specialty]` | `professional.specialty` | Especialidade |
| `[flashsite_prof_license]` | `professional.license` | CRM / OAB / CAU |
| `[flashsite_prof_secondary_id]` | `professional.secondary_id` | RQE ou ID secundário |

---

### Dynamic Tags Elementor — campos profissionais

Cinco novas tags registadas no grupo `FlashSite Core`:

| Tag | Campo |
|---|---|
| FlashSite: Nome de Exibição | `professional.display_name` |
| FlashSite: Título Profissional | `professional.title` |
| FlashSite: Especialidade | `professional.specialty` |
| FlashSite: Registo Profissional (CRM/OAB/CAU) | `professional.license` |
| FlashSite: Registo Secundário (RQE) | `professional.secondary_id` |

---

## Compatibilidade

- Testado contra Elementor 3.x (Data_Tag) e Elementor 3.21+ (Tag).
- Nenhuma breaking change. Todos os shortcodes e tags da 2.3.0 mantêm comportamento idêntico.
- `[flashsite_hours]` não foi alterado — continua a funcionar como antes para quem já o usa. `[flashsite_hours_list]` é o substituto recomendado para novos projetos.

## Validação

- `php -l` executado em todos os ficheiros PHP modificados e adicionados.
- `FLASHSITE_CORE_VERSION` e header `Version:` em sync em `2.4.0`.
