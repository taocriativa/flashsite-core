# FlashSite Core 2.6.0-beta.9

## Telefones validados por país
- Campos de telefone (type=tel ou nome com tel/phone/telefone/whats) aceitam só números, espaços, + ( ) - .
- PT: 9 dígitos começados por 2, 3 ou 9 · BR: DDD + 8 ou 9 dígitos · INT: 7 a 15 dígitos.
- Com indicativo (+351, +55, 00…) valida pelo país do indicativo; outros indicativos: 8 a 15 dígitos.
- País em FlashSite › WhatsApp e formulários (automático pelo país dos Dados do Negócio).
- Envio bloqueado com a mensagem do browser enquanto houver telefone inválido.

## E-mail dos formulários mais robusto
- Destinatário-modelo `pedidos@formularios.flashsite.invalid` na ação E-mail: o Core troca-o pelo
  e-mail do painel no `wp_mail` (não depende de o Elementor resolver tags dinâmicas no envio).
- Envio de formulário sem destinatário: vai para o e-mail do painel.
- Painel mostra o "Último e-mail de formulário" (data, destinatário e erro, se houver).
