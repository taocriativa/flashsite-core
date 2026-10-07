# 2.6.0-beta.22

- Página de um item: o `<body>` recebe `fs-item`, `fs-item-<taxonomia>-<termo>` (ex.: `fs-item-estado-reservado`) e `fs-item-fechado` quando o termo está em `settings.closed_terms` (ou, sem esta chave, em `unavailable_terms`). Imobiliário: fechado = reservado, vendido ou arrendado.
- Classes utilitárias novas (fsc-effects.css), a usar no campo "Classes" do Elementor:
  - `fs-esconde-vazio`: a linha (rótulo + valor) some quando o valor dinâmico vem vazio.
  - `fs-so-com-link`: o botão some quando o link dinâmico está vazio (ex.: vídeo ou visita virtual).
  - `fs-so-aberto` / `fs-so-fechado`: mostrar ou esconder conforme o item esteja fechado (ex.: "Marcar visita" vs. "Este imóvel já não está disponível").
  - No editor do Elementor nada é escondido.
- Popups: o diálogo ganha nome acessível (o primeiro título) e os links `#fechar` passam a ter `role="button"` e fecham também com a tecla Espaço.
