# 3.1.0-beta.5

- **Busca do site com a cara do site:** a página de resultados (`?s=termo`) passa a ser a listagem da coleção principal (ex.: imóveis), com o topo, os filtros, os cartões e o fim das páginas modelo. Antes, abria o modelo cru do tema, sem cabeçalho, rodapé nem cores do site.
  - Linha no topo: "Resultados para “termo”", quantos foram encontrados e "Ver todos".
  - Os filtros refinam a busca (o termo é mantido).
  - Sem resultados: "Nada encontrado para esta busca." e o link para ver todos.
  - Textos ajustáveis em `archive_texts` (`search_results`, `search_count`, `search_empty`).
- Sites sem coleção não mudam.
