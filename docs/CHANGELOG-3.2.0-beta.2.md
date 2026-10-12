# 3.2.0-beta.2

- **Correção crítica:** a beta.1 derrubava o site ao ativar ("Ocorreu um erro crítico"). O módulo novo `Multilingual` não implementava `boot()`, `isActive()` e `getSlug()` do contrato `ModuleInterface`, e o PHP para ao carregar a classe.
- **Teste novo `ModulesListTest`:** carrega todos os módulos de `config/modules.php` e confirma que cumprem o contrato. Antes de cada ZIP, o plugin também é carregado e inicializado num simulador de WordPress.
- Conteúdo da beta.1 mantido (idioma da página com Polylang, pacotes en, es, fr).
