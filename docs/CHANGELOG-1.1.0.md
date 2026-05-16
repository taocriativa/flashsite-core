# FlashSite Core 1.1.0 — Changelog

## Objetivo da release
Consolidar a base 1.0.0 antes da expansão funcional, adicionando infraestrutura mínima de testes, documentação operacional e preparação de upgrade segura.

## Alterações principais
- bump de versão para `1.1.0` no plugin e defaults;
- registro explícito de upgrade `1.1.0` no `SchemaManager` via `Application` para reforçar migração e `ensureRole()` em upgrades;
- adição de `phpunit.xml` e estrutura `tests/`;
- adição de runner nativo `php tests/run.php` para validação local sem Composer/PHPUnit;
- adição de documentação de plano de testes, checklist de regressão e contrato de output.

## Nota
Esta release não altera o conceito central do plugin nem introduz integrações externas novas. O foco é hardening e governança da evolução.
