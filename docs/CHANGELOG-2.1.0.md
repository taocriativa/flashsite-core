# FlashSite Core 2.1.0

## Alterações
- Renomeia o menu administrativo principal para `FlashSite`, preparando o painel unificado de gestão.
- Adiciona a constante `FLASHSITE_CORE_HAS_SHELL` e a função `flashsite_core_has_shell()` para detecção explícita do painel unificado por plugins complementares.
- Ajusta a entrada principal para `Visão Geral`, mantendo `Dados do Negócio`, `Setup Wizard` e `Segurança de Dados` no mesmo painel.
- Atualiza a assinatura visual do painel para `FlashSite · Painel de Gestão`, mantendo a logo FlashSite no cabeçalho.
- Implementa `InstallationGuard::canWriteSensitive()` e `InstallationGuard::blockReason()` para evitar erro fatal nos fluxos de gravação protegida.
- Corrige o runner de testes para não depender de testes legados de Hero removidos do Core.

## Validação
- `php -l` executado em todos os arquivos PHP do plugin.
- `php tests/run.php` executado com 14 grupos de teste aprovados.
