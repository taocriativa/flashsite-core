# FlashSite Core 2.3.0

## Alterações
- Actualiza header do plugin: autor passa a "Flash Site", sem URL de autor.
- Actualiza Plugin URI para https://www.flashsite.pt.
- Actualiza descrição: "FlashSite" → "Flash Site".

## Correções
- **Telefone:** `Formatters::phoneSanitize()` preserva caracteres de formatação `+ ( ) - . espaço` ao salvar. Anteriormente, `phoneDigits()` strip todos os não-dígitos, impedindo formatos como `(351) 912.345.678`. O `tel:` e `wa.me/` continuam a usar apenas dígitos via `phoneDigits()`.
- **Logos / Media Uploader:** `wp_enqueue_media()` adicionado em `Assets::registerAdminAssets()` para qualquer página `flashsite-*`. O botão "Selecionar ficheiro" dependia de `wp.media()` que não estava a ser carregado, resultando em clique sem resposta.

## Compatibilidade
- Testado em WordPress 7.0 "Armstrong" (lançado 20/05/2026). Nenhuma breaking change identificada.

## Validação
- `php -l` executado nos ficheiros PHP modificados.
- Confirmado que `FLASHSITE_CORE_VERSION` e o header `Version:` estão em sync em `2.3.0`.
