# 3.2.0-beta.6

Instalar só no demo-surf-school.flashsite.pt (site piloto). Nunca no flashsite.pt.

- **Fontes locais** (FlashSite › Fontes, só administrador): o Core lê as fontes das variáveis do Elementor e da tipografia do kit, baixa do Google só os pesos da hierarquia padrão (títulos 700/800, texto 400/600/700, ajustáveis na tela) e só o alfabeto latino, e guarda em `uploads/flashsite-fonts/`. No site: pré-carrega a fonte do texto e a dos títulos, escreve o CSS no `<head>` e desliga as fontes do Google do Elementor (incluindo Roboto e Roboto Slab). Cada fonte ganha uma reserva com as mesmas medidas (tabela de 55 fontes em `config/fonts/metrics.php`; fora da tabela, Arial sem ajuste). As variáveis de fonte do Elementor passam a ter a pilha completa (`"Fonte","Fonte Reserva",Arial,sans-serif`), sem Times New Roman a aparecer antes. "Desligar" volta às fontes do Google. Ao preparar ou desligar, a cache do LiteSpeed é limpa página a página (beta.8: nunca "limpar tudo"). REST `flashsite/v1/fonts` (GET; POST `{action: prepare|disable, weights}`).
- **Correção da beta.5:** a rota REST dos dados por idioma passa a `flashsite/v1/business-translations` (a antiga colidia com `business-profile/<secção>`).
- Teste novo `FontsTest` (com o CSS real do Google como amostra).
