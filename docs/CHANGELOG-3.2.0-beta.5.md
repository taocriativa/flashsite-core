# 3.2.0-beta.5

Instalar só no demo-surf-school.flashsite.pt (site piloto). Nunca no flashsite.pt.

- **Dados por idioma** (FlashSite › Dados por idioma, só com o Polylang): frase curta, título profissional, especialidade, horário de cada dia e observação dos horários têm versão por idioma. Nome, contactos e morada são os mesmos em todos. Campo vazio mostra o texto do idioma principal. As tags do Elementor ("FlashSite: Horário · Observação" etc.) mostram o idioma da página. REST `flashsite/v1/business-profile/translations` (GET/POST).
- **Seletor de idiomas `fs-idiomas`:** um parágrafo com a classe global `fs-idiomas` mostra os idiomas do site, com link para a tradução da página atual e o idioma atual marcado (`is-ativo`). Base de estilo em fsc-effects.css.
- **Páginas modelo por idioma:** com o Polylang, o Core usa a tradução da página "Modelo · …" no idioma da página (filtro `flashsite/model_page_id`). Sem tradução, usa a original.
- **Preço no formato do idioma:** em inglês "€45.00" (símbolo antes, ponto decimal); nos outros idiomas, como a moeda define ("45,00 €"). O painel continua com o formato do mercado.
- Teste novo `MultilingualBeta5Test`.
- Fica para depois: faixas de preço com "mil"/"milhões" (imobiliário) em inglês e francês; horário formatado completo (lista de dias) por idioma.
