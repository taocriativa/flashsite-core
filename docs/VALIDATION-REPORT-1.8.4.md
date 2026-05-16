# FlashSite Core 1.8.4 — Relatório de Validação

## Cenários avaliados
1. Renderização do shortcode Hero com múltiplos slides
2. Renderização do shortcode Hero com múltiplas instâncias na mesma página
3. Enfileiramento dedicado de CSS/JS do Hero
4. Ausência de payload inline (`<style>`/`<script>`) no HTML do shortcode
5. Regressão geral da suíte existente do projeto

## Resultado
- Suíte total executada: 16 grupos de teste
- Resultado: todos aprovados

## Cobertura adicionada nesta iteração
- `ManagedHeroRenderModuleTest`
  - valida enqueue de assets do Hero
  - valida markup sem inline style/script
  - valida suporte a mais de uma instância do shortcode

## Risco residual conhecido
- a validação de lifecycle do editor Elementor aqui é indireta, baseada no contrato JS e na remoção do inline payload
- a confirmação final em ambiente WordPress + Elementor real continua recomendada após instalação do ZIP
