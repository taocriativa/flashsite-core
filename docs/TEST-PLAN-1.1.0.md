# FlashSite Core 1.1.0 — Plano de Testes

## Escopo coberto
- `BusinessProfile`
- `BusinessValidator`
- `Container`
- `ModuleManager`
- `BusinessRepository`
- `OnboardingRepository`
- `RoleManager`

## Tipos de teste
### Unit
- normalização de payload;
- imutabilidade prática via ausência de setters e `toArray()` consistente;
- sanitização e validação de campos;
- memoização do container;
- ciclo de `register()` e `boot()` em módulos.

### Integration
- persistência em option store in-memory;
- migração de legado para a estrutura atual;
- reset de onboarding;
- criação e reforço de capabilities da role.

## Execução local
```bash
php tests/run.php
```

## Validação complementar recomendada em WordPress real
- ativar/desativar plugin;
- concluir wizard;
- salvar contacto sem apagar branding;
- salvar branding sem apagar identity/contact;
- validar shortcodes principais;
- validar Elementor ativo/inativo.
