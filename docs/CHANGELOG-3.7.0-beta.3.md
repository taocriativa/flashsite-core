# FlashSite Core 3.7.0-beta.3 · Modo Criador: base visível e travada

Base: 3.7.0-beta.2. Decisão do Diretor (06/11): o modo Criador limpa o painel, não prende o dono. Beta só para site de demonstração.

## Mudou
- **Plugins de base aparecem para o dono, travados.** Core, kits `*-kit`, `flashsite-*`, Elementor e Elementor Pro voltam à lista de plugins sem Desativar, sem Apagar e sem caixa de seleção, com a nota "Gerido pela Flash Site". Links como Definições continuam.
- **Os outros plugins ficam livres** para o dono ativar e desativar. Continuam ocultos só os técnicos (FULL.Cliente, LiteSpeed, Code Snippets, WPS Hide Login, Simple History, Hostinger, MCP Adapter).
- Instalar, apagar e atualizar plugins e temas continua com o Criador. As atualizações automáticas geridas pela Flash Site vêm na 3.8.0.

## O site é do cliente
- Apagar a linha `FLASHSITE_CRIADOR` do `wp-config.php` (hospedagem no nome do cliente) desliga o modo e devolve tudo ao administrador. Entra no kit de entrega.

## Testes
- 47 grupos ok, `tests/smoke-boot.php`: BOOT OK. PHP 8.2.27.
- demo-imobiliario (06/11, por SSH): dono-teste vê 6 plugins (5 de base com "Gerido pela Flash Site", sem Desativar; Rank Math livre). O Criador vê os 15.
