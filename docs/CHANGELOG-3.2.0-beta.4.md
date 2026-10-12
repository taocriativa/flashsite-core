# 3.2.0-beta.4

Instalar só no demo-surf-school.flashsite.pt (site piloto). Nunca no flashsite.pt.

- **Coleções: itens por idioma.** Em FlashSite › Coleções, com o Polylang ativo, aparece "4. Idiomas" com uma caixa por coleção ativa. Marcada, a coleção entra no Polylang: cada item tem a sua versão em cada idioma (botão + na lista de itens) e as listas mostram só os itens do idioma da página. Desmarcada (padrão), a coleção é compartilhada: um item só, com os rótulos do Core traduzidos.
- **REST `flashsite/v1/translations`** (para o MCP e para quem monta o site):
  - `GET /translations/<id>`: idioma e traduções de uma página ou item.
  - `POST /translations/<id>` `{language}`: define o idioma de um conteúdo sem idioma.
  - `POST /translations` `{source, language, title?, slug?, status?, content?, excerpt?, meta?}`: cria a tradução copiando campos, imagem, termos e Elementor do original, e liga as duas no Polylang.
- A ligação ao Polylang fica num adaptador próprio (`PolylangBridge`), carregado só com o Polylang ativo.
- Páginas modelo por idioma passam para a beta.5.
- Teste novo `MultilingualCollectionsTest`.
