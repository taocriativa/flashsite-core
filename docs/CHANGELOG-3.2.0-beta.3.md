# 3.2.0-beta.3

Instalar só no demo-surf-school.flashsite.pt (site piloto). Nunca no flashsite.pt.

- **Textos do site por idioma.** Com o Polylang, cada idioma tem a sua página com as mesmas chaves. A tela FlashSite › Textos do site ganha uma aba por idioma; cada aba mostra só os textos das páginas daquele idioma. A página mostra sempre o valor do seu idioma e, sem valor, o texto padrão da própria página.
- **Sem migração.** O idioma principal continua a guardar com a chave simples, como na 3.1. Os outros idiomas guardam como `chave@idioma` (ex.: `inicio_titulo@en`). Sites de um idioma não mudam nada.
- "Página inicial" reconhecida também nas traduções da página inicial.
- **Mercados × Polylang:** com o Polylang ativo, os Mercados não imprimem hreflang PT/BR nem o aviso de país (os idiomas vêm do Polylang). A tela dos Mercados avisa disso. As abas por país continuam.
- Contrato `LanguageContextInterface` ganha `ofPost()` (idioma de uma página).
- Testes: idiomas nos Textos do site (chaves, abas, valor por idioma, campos antigos, idioma desconhecido ignorado) e Mercados sem Polylang.
