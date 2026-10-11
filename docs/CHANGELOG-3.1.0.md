# 3.1.0 · versão estável

Reúne as betas 3.1.0-beta.1 a beta.16, validadas na Milielle Oliveira, na BMCito e no flashsite.pt. Detalhe de cada uma em `CHANGELOG-3.1.0-beta.N.md`.

## Painel do cliente
- **Textos do site:** o cliente muda títulos e textos num formulário, sem abrir o Elementor (tag "Texto do site (editável pelo cliente)"). Páginas na ordem do site, "Mostrar no site", barra de guardar fixa.
- **Tamanho previsto** em cada texto (contador, aviso em vermelho, trava com 20% de folga).
- **O que pode editar:** primeira tela do painel com atalhos para coleções, textos, dados do negócio, WhatsApp e formulários, política e fotos.
- **Perfil Cliente** (antigo "Gestor de Site"): sem Elementor, plugins e telas técnicas; não exclui fotos em uso; barra do topo limpa; botão de IA da Hostinger (Kodee) escondido.
- **Aviso de acesso administrativo** nas telas sensíveis quando o site tem usuário Cliente.
- **Simple History** como dependência recomendada: só administradores veem, 365 dias.
- Logo atual "Flash Site" (sem S) nas telas do Core.

## Coleções (Imobiliário)
- Baixa de preço (preço anterior guardado sozinho, selo "Preço reduzido", tag `flashsite-item-price-drop`).
- Listas na página do item mostram cada item (ID de consulta `flashsite_outros` tira o item atual).
- Tipos de imóvel ampliados (BR e PT) e tipo novo criado na ficha; termos novos chegam com a atualização.
- Galeria mais leve (miniaturas com `loading="lazy"` e `sizes`); filtro de localização sem travessão.
- Busca do site (`?s=`) com o visual da listagem da coleção.

## Recursos para qualquer site
- Abas (e-tabs) pelo endereço (`#brasil`) e pelo país do visitante (`fsc-tabs.js`).
- Mapa do Google pelos Dados do negócio: classe `fs-mapa` e `[flashsite_maps]`, guardando só o endereço do embed.
- Topo fixo `fs-topo-fixo` com `is-rolado` depois de 40 px.
- Contador `fs-contador` (0 até o valor ao aparecer na tela).
- Efeitos, popups e modelos carregam também em sites sem coleções.
