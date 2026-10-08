# 3.0.0

Versão estável. Mesmo código da 3.0.0-rc.3, validada na cópia do flashsite.pt (staging.flashsite.pt). Substitui a 2.5.2.

## O que traz em relação à 2.5.2
- **País do site (Portugal / Brasil):** termos, campos, regras e endereços das coleções conforme o país (Imobiliário, Restaurante e Planos com camada Brasil). Detalhe em beta.1 e beta.2.
- **Imobiliário no Brasil:** venda e aluguel no mesmo imóvel, lançamentos, faixas de preço com R$ nos dois extremos.
- **WhatsApp com mensagem pronta** na tag de link, com `{titulo}`, `{referencia}` e `{link}`.
- **Fontes com número no nome** (ex.: "Source Sans 3") carregam corretamente.
- **Mercados do site:** pares de páginas PT/BR com hreflang, aviso para quem chega do outro país (fuso horário e idioma, sem redirecionar), cookie `flashsite_region`, shortcode `[flashsite_market_switch]` e rota REST `flashsite/v1/markets`. O aviso fica abaixo do cabeçalho fixo quando há barra de cookies em baixo.
- **Segurança:** Permissions-Policy `camera=(), microphone=()` (sem bloquear pagamentos nem mapas); XML-RPC ligado quando o Jetpack está ativo.
- **Campos numéricos:** zero não se mostra.

## Ao atualizar
- Depois de instalar: LiteSpeed Cache › Limpar tudo (o JS combinado não se regenera sozinho).

Detalhe por versão: CHANGELOG-3.0.0-beta.1 a CHANGELOG-3.0.0-rc.3.
