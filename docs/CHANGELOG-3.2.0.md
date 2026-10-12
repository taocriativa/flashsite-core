# 3.2.0 · versão estável

Reúne as betas 3.2.0-beta.1 a beta.8, validadas no demo surf (PT, EN, FR). Detalhe em `CHANGELOG-3.2.0-beta.N.md`. Sites com um idioma só não mudam nada.

## Sites com mais de um idioma (Polylang)
- O Core reconhece o idioma da página. Textos fixos do Core em pt-PT, pt-BR, en, es e fr.
- **Textos do site por idioma:** uma aba por idioma. O idioma principal mantém as chaves de sempre, sem migração.
- **Dados por idioma** (FlashSite › Dados por idioma): frase curta, título, especialidade e horários com versão por idioma.
- **Coleções:** compartilhadas (padrão) ou com itens por idioma (FlashSite › Coleções › Idiomas).
- **Páginas modelo por idioma**, incluindo os popups.
- **Seletor `fs-idiomas`:** um parágrafo com a classe mostra os idiomas, com link para a tradução da página.
- **Preço no formato do idioma:** em inglês, "€45.00".
- **Mercados × Polylang:** com o Polylang, os Mercados não imprimem hreflang PT/BR nem o aviso de país.
- **Painel do cliente:** cartão "Idiomas do site".
- **REST para o MCP:** `flashsite/v1/translations` e `flashsite/v1/business-translations`.

## Qualquer site
- **Fontes locais** (FlashSite › Fontes, só administrador): fontes do site servidas pelo próprio site, só os pesos da hierarquia padrão (títulos 700/800, texto 400/600/700, ajustáveis), pré-carregadas, com fonte de reserva de mesmas medidas. Sem troca visível ao carregar e sem Google Fonts. Só atua depois de alguém clicar em "Preparar". A cache do LiteSpeed é limpa página a página.

## Qualidade
- Teste de carga do plugin antes de cada ZIP (`tests/smoke-boot.php`); testes de contrato dos módulos, idiomas, coleções por idioma, dados por idioma, preço por idioma, seletor e fontes.
