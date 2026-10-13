# 3.3.0

## beta.1
- **Correção:** `ModelPages::pageId()` devolvia da cache o id da página modelo sem a tradução do Polylang. Só a primeira chamada de cada pedido era traduzida, por isso a listagem e a página de cada item em EN e FR usavam os modelos do idioma principal. Agora a tradução é resolvida em todas as chamadas.
