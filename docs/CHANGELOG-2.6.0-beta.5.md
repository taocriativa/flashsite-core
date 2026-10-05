# FlashSite Core 2.6.0-beta.5

## Correções
- Páginas modelo passam a carregar o CSS atómico do Elementor 4 (base/global/local) e o CSS de cada modelo no `<head>`: as páginas `/imoveis/` e de cada imóvel ficam com o design completo.
- Números sem separador de milhares abaixo de 10 000 ("2015", não "2.015").
- Listagem sem resultados: "Não encontrámos imóveis com estes filtros."

## Novo · modelos do site (funcionam mesmo sem coleções)
- `Modelo · 404` → página de erro 404 (fase 0.6 sem Theme Builder).
- `Modelo · Cabeçalho` + `Modelo · Rodapé` → envolvem as páginas de texto feitas no editor do WordPress (ex.: Política de Privacidade, Termos).
- A página de privacidade do WordPress mostra automaticamente o texto de FlashSite › Política de Privacidade, com a data de atualização.
