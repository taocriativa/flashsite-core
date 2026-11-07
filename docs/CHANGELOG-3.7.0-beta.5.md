# 3.7.0-beta.5 (feature/3.7.0)

Diferenciais de restaurante, fase A (aprovada pelo Diretor em 07/11/2026). Ver `JOB_FLS_26-11_piloto-blocos/_# Producao/Estrategia/DIFERENCIAIS_RESTAURANTE.md`.

- **QR da carta** (FlashSite › QR da carta): QR em SVG para a lista pública de cada coleção (ex.: /menu/), gerado no navegador com qrcode-generator 1.4.4 (MIT, `assets/admin/vendor/`). Descarregar SVG e imprimir. Só aparece quando há coleção com página de lista.
- **Bloco "Filtrar carta"** (`flashsite/menu-filter`): Vegetariano, Vegano, Sem glúten e "Sem…" com os 14 alergénios do preset; esconde no navegador os pratos que não servem; aviso editável "confirme com a equipa". Usa classes `fs-dieta-*` / `fs-alerg-*` acrescentadas aos pratos (só em temas de blocos).
- **Bloco "Aberto agora"** (`flashsite/open-now`): lê o horário dos Dados do Negócio (classe `OpeningHours`) e calcula no navegador, no fuso do site; passa da meia-noite; se algum dia for ilegível, não aparece.
- **Bloco "Informação legal"** (`flashsite/legal`): PT = IVA incluído + couvert (opcional) + Livro de Reclamações; BR = texto opcional da lei estadual/municipal. Segue o mercado do site.
- **Seção pronta "Grupos e eventos"** no tema FlashSite PRO 0.3.0 (formulário com data, pessoas, tipo e orçamento).
- Testes: `OpeningHoursTest`; 49 grupos a passar.
- Compatibilidade: demo-restaurante real (Elementor) com HTML idêntico nas 6 páginas.
