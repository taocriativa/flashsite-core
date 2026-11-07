# FlashSite Core 3.7.0 · Modo Criador e sites em blocos

Base: 3.6.0. Versão estável que reúne a 3.7.0-beta.1 a beta.7. Um só Core para sites feitos com Elementor e para sites novos em blocos (tema FlashSite PRO), sem Elementor.

## Novo
- **Modo Criador** (opcional): liga-se com `define('FLASHSITE_CRIADOR', 'login');` no `wp-config.php`. Limpa o painel do dono (plugins técnicos ocultos, base visível e travada, sem instalar/atualizar plugins e temas). **Sem a constante, nada muda.** Detalhe: `CHANGELOG-3.7.0-beta.1.md` a `beta.3.md`.
- **Módulo Blocos** (só atua em sites com tema de blocos):
  - Ligações de dados `flashsite/business` e `flashsite/item` para títulos, parágrafos, botões e imagens nativos, com os valores reais também no editor.
  - Blocos: Formulário (+ campos), Horário, Mapa, Ficha do item, Aberto agora, Filtrar carta (dieta e 14 alergénios), Informação legal (PT/BR).
  - Efeitos de movimento por bloco (entrada, revelar, parallax, balanço), respeitando "reduzir movimento".
  - Modelos de página do item e da lista de cada coleção; destaque da coleção no Query Loop (`fsDestaques`).
  - Pedidos do formulário guardados em FlashSite › Pedidos do site (privado, apagados ao fim de 12 meses).
  - Secções travadas: o dono edita conteúdo, não o layout.
- **QR da carta** (FlashSite › QR da carta) para qualquer site com coleção pública (SVG para gráfica).

## Compatibilidade com Elementor
- Verificada no demo-restaurante real (Elementor 4.3.6 + Pro 4.3.3): HTML de 6 páginas idêntico antes e depois, formulário Elementor com validação e WhatsApp a funcionar, mesmos pedidos de rede.
- Num site Elementor o módulo Blocos não carrega CSS nem JS no site público; dependências pedidas continuam as mesmas (Elementor, Elementor Pro, Rank Math, WPS Hide Login, Simple History). A única tela nova é "QR da carta", quando há coleção com página de lista.

## Inalterado
- Dynamic Tags, formulários Elementor, coleções, Dados do Negócio, endpoints REST, abilities, multilíngue. `FLASHSITE_DATA_VERSION` continua 2.6.0. O upgrade não cria conteúdo nem ativa coleções.

## Testes
- 49 grupos ok (inclui `CreatorModeTest`, `BlocksTest`, `OpeningHoursTest`), `tests/smoke-boot.php`: BOOT OK. PHP 8.2.27.
- Piloto demo-restaurante-blocos.flashsite.pt: Lighthouse celular 99 (o mesmo demo em Elementor: 91), acessibilidade 100; editor testado com sessão real de administrador.
