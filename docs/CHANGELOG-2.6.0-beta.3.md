# FlashSite Core 2.6.0-beta.3

## Novo
- Tags das Coleções podem apontar para um item de uma lista, sem loop: "Item em destaque n.º N" ou "Item mais recente n.º N" (controlos Item / N.º / Coleção). Permite montar pelo MCP os cartões de destaque da página inicial, que o MCP não consegue fazer com loops.
- Itens vendidos/arrendados ficam fora destas listas.
- Nova tag "Item · Título"; a tag "Item · Link" ganha a opção "Página do item".

## Nota
- O loop atómico (`e-collection-loop`) não é criável pelo MCP do Elementor ("tipo de elemento desconhecido"). Listagens completas com filtros continuam a exigir Loop Grid / Theme Builder no editor.
