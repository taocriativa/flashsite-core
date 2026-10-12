# 3.2.0-beta.1

Primeira etapa dos sites multilíngues (plano: `FlashSite_Ecosystem/Docs/Planos-Core/PLANO_Core-3.2.0_Multilingue.md`).

- **Idioma da página:** contrato `LanguageContextInterface` no Domain. Sem plugin de tradução, o idioma é o do mercado (pt-PT ou pt-BR), como sempre. Com o **Polylang** ativo, o módulo novo `Multilingual` passa a ler o idioma de cada página (pt-PT, pt-BR, en, es, fr). Filtro `flashsite/language` para forçar.
- **Pacotes de idioma** em `config/i18n/` (en, es, fr): traduzem as frases públicas do Core e dos presets pelo texto original em português. Português não muda nada.
- **Textos do site que passam a sair no idioma da página:** listagens (vazio, ver todos, filtros, paginação, busca), nomes de classificações e termos dos presets (finalidade, tipo, estado, modalidade, categorias do menu…), preço sob consulta, "desde" / "a partir de", períodos (/mês, /semana…), selo "Preço reduzido", botão fechar dos popups e mensagens de validação de telefone e do WhatsApp dos formulários.
- Fora desta beta (próximas): Textos do site e Dados do negócio por idioma, páginas modelo por idioma, faixas de preço, seletor `fs-idiomas`.
- Testes: I18nTest (28 grupos).

Sites com um idioma não têm mudança visível.
