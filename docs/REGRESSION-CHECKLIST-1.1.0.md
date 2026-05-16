# FlashSite Core 1.1.0 — Regression Checklist

## Persistência
- [ ] salvar contacto mantém branding existente
- [ ] salvar branding mantém contacto e identity existentes
- [ ] campos ausentes não apagam dados prévios
- [ ] migração legado → estrutura atual ocorre sem perda relevante

## Wizard
- [ ] inicia na etapa correta
- [ ] retoma após refresh
- [ ] marca conclusão corretamente
- [ ] resumo final reflete dados persistidos

## Output
- [ ] shortcode de nome retorna texto escapado
- [ ] shortcode de email retorna fallback quando vazio
- [ ] shortcode de logo não quebra sem attachment
- [ ] Dynamic Tags não quebram com Elementor ausente

## Access control
- [ ] role `Gestor de Site` existe após ativação
- [ ] administrator mantém capabilities do plugin

## Instalação e ciclo de vida
- [ ] ativação define versões
- [ ] upgrade para 1.1.0 executa migração/ensureRole
- [ ] uninstall permanece conservador
