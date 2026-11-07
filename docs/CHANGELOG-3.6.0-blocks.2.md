# 3.6.0-blocks.2 (piloto, branch feature/blocks)

Efeitos de movimento nos blocos (src/Modules/Blocks/Effects.php, assets/blocks/efeitos/):
- Painel "Efeito de movimento" em todos os blocos core/ e flashsite/: Entrada suave, Revelar imagem, Parallax suave, Balanço subtil; atraso em ms para sequências.
- Atributos fsEffect/fsDelay registados no servidor; a classe `fs-fx fs-fx--<efeito>` entra só na renderização (o HTML gravado não muda).
- CSS e JS (≈2,4 KB) só carregam em páginas com efeito. Entrada/revelar por IntersectionObserver; parallax por animation-timeline (só onde o navegador suporta); "reduzir movimento" desliga tudo; sem JS nada fica escondido; o que já está à vista ao abrir não anima.
- Medido no piloto (07/11/2026), celular: 99 com e sem efeitos, CLS 0.
- Depois de publicar por rsync a partir do Drive, correr `chmod -R u+rwX,go+rX` na pasta do plugin: o --chmod do rsync não corrige pastas que já existiam com 700.
