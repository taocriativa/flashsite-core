# FlashSite Core 2.6.0-beta.4 · Páginas modelo (sem Theme Builder)

## Novo
- **Páginas modelo**: o design da página de cada item, do cartão e da listagem faz-se em páginas normais do Elementor (montáveis pelo MCP, podem ficar em rascunho). O Core encontra-as pelo título e usa-as:
  - `Modelo · Imóvel` → página de cada imóvel (`/imoveis/<slug>/`)
  - `Modelo · Cartão de imóvel` → cartão repetido por imóvel na listagem
  - `Modelo · Imóveis topo` / `Modelo · Imóveis rodapé` → topo e fim da listagem
- **Listagem** `/imoveis/` (e `/imoveis/<taxonomia>/<termo>/`): filtros por finalidade, tipo, tipologia, zona e faixa de preço, combináveis; grelha 3/2/1 colunas; paginação; estado "sem resultados".
- Tags "Item atual" funcionam dentro das páginas modelo; no editor, mostram um imóvel real como exemplo.
- Nova tag "Item · Descrição".
- Sem página modelo, o WordPress usa o template normal do tema (comportamento anterior).
