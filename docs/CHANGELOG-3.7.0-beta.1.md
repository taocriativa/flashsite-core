# FlashSite Core 3.7.0-beta.1 · Modo Criador

Base: 3.6.0. Beta para teste em site de demonstração. Não instalar no flashsite.pt nem em site de cliente.

## Novo
- **Modo Criador: um nível acima do administrador.** Liga-se com uma constante no `wp-config.php` (login ou e-mail, vários separados por vírgula):
  `define('FLASHSITE_CRIADOR', 'login-da-flash-site');`
  Sem a constante, nada muda no site.
- Para quem não é Criador (o dono do site, mesmo sendo administrador):
  - **Plugins técnicos ocultos** (FULL.Cliente, LiteSpeed Cache, Code Snippets, WPS Hide Login, Simple History, plugins da Hostinger): saem da lista de plugins, dos menus, dos avisos, do rodapé do painel (janela do FULL), do Painel do WordPress e da barra superior. Abrir a tela pela URL dá 403. A origem de cada aviso ou menu é identificada pelo arquivo que o registou, sem depender de nomes.
  - **Plugins de base protegidos** (`flashsite-*`, `*-kit`, Elementor, Elementor Pro): saem só da lista de plugins, para não serem desativados por engano. Os menus continuam.
  - Sem instalar, enviar, editar, apagar ou atualizar plugins e temas; sem trocar de tema; sem atualizar o WordPress nem traduções. Os avisos de atualização dos plugins ocultos também somem.
  - O utilizador Criador não aparece em Utilizadores e não pode ser editado, excluído nem rebaixado.
  - Painel do WordPress sem notícias e sem o painel de boas-vindas.
- Filtros: `flashsite_core_creator_hidden_plugins`, `flashsite_core_creator_protected_plugins`, `flashsite_core_creator_blocked_caps`, `flashsite_core_creator_clean_dashboard`.

## Limites
- Atua só dentro do WordPress. Hospedagem, FTP e base de dados ficam fora (decisão do Diretor, 06/11).
- WP-CLI, cron e atualizações automáticas da hospedagem não são afetados.
- O utilizador do MCP (senha de aplicação) segue as mesmas regras: se precisar instalar ou atualizar plugins pelo MCP, entra na constante.
- Campos que plugins ocultos põem em telas do WordPress (ex.: o endereço de login do WPS Hide Login em Definições › Geral) continuam visíveis.

## Inalterado
- Papel Gestor Flash Site, coleções, Dados do Negócio, endpoints REST, abilities. `FLASHSITE_DATA_VERSION` continua 2.6.0. O upgrade não cria conteúdo.

## Testes
- 47 grupos ok (novo `CreatorModeTest`), `tests/smoke-boot.php`: BOOT OK. PHP 8.2.27.
