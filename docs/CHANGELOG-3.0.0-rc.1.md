# 3.0.0-rc.1

Candidata ao 3.0.0. O que muda em relação ao beta.6:

- **Segurança (Hardening):** o Permissions-Policy padrão passa a `camera=(), microphone=()`. Sem `payment` (bloqueava Apple Pay e Google Pay do WooPayments/Stripe) e sem `geolocation` (mapas). Valor ajustável pelo filtro `flashsite_core_permissions_policy`.
- **XML-RPC:** continua desligado por padrão, mas fica ligado quando o Jetpack está ativo (o Jetpack liga-se ao WordPress.com por ele).
- **Campos numéricos:** zero não se mostra ("0 quartos", "0 vagas" ficam vazios, como campo não preenchido).
- **Mercados do site:** rota REST `flashsite/v1/markets` (GET e POST, só administradores) para ler e gravar pares e textos sem o formulário do painel.

Plano das versões: 3.0.0 = estabilização (mercado BR/PT, Mercados do site, segurança, WhatsApp com mensagem, fontes). Funções grandes (página "O que pode editar", papel Cliente, Manual do Gestor, classes utilitárias automáticas, dados das demos no Core, painel em PT-BR, modelo Saúde, galeria em tela cheia) passam para o 3.1.0.
