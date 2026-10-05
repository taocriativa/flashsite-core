# FlashSite Core 2.6.0-beta.8 · WhatsApp e formulários

Novo módulo `Contact` (FlashSite › WhatsApp e formulários).

## Formulários → WhatsApp (sem API paga)
- Depois de um envio com sucesso, a mesma aba abre `wa.me/<WhatsApp do negócio>` com o resumo
  "Rótulo: valor" de todos os campos preenchidos. Navegação na aba atual: nunca é bloqueada.
- Funciona com o formulário do Elementor 4 (atómico: classe `form-state-success`) e com o
  formulário clássico do Elementor Pro (evento `submit_success`).
- Campos lidos sozinhos; caixas de consentimento (autorizo/RGPD/LGPD/privacidade) ficam de fora.
- Modos: todos os formulários / só com a classe `fs-form-whatsapp` / desligado. `fs-form-no-whatsapp` exclui.
- Primeira linha da mensagem configurável.

## Formulários → e-mail
- Nova tag "FlashSite: E-mail para pedidos (só formulários)" (`flashsite-forms-recipient`) para o
  campo "Para" da ação E-mail do formulário. Devolve `contact.email_admin` (senão o e-mail público,
  senão o do WordPress) apenas durante o envio AJAX do formulário; fora disso não mostra nada.
- Entrega fiável exige SMTP (e-mail do domínio ou Gmail com palavra-passe de aplicação). Nota no painel.

## Botão flutuante de WhatsApp
- Ligado/desligado, canto direito/esquerdo, páginas (todas/inicial/menos a inicial), dispositivos,
  cor do site (`fs-cor-destaque`) ou verde do WhatsApp, texto opcional, mensagem inicial.
