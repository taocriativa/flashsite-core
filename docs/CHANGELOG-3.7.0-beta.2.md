# FlashSite Core 3.7.0-beta.2 · Modo Criador (ajustes do teste)

Base: 3.7.0-beta.1. Ajustes vistos no teste do demo-imobiliario (06/11). Beta só para site de demonstração.

## Mudou
- **Avisos de dependências do Core só para o Criador.** "WPS Hide Login não está instalado", "Simple History não está instalado" e semelhantes deixam de aparecer para o dono quando o modo Criador está ligado. O estado das dependências continua a ser gravado. Novo filtro: `flashsite_core_dependency_notices`.
- **MCP Adapter entra nos plugins ocultos** (`mcp-adapter`): sai da lista de plugins do dono, junto com o aviso de versão duplicada.

## Inalterado
- Tudo o resto da beta.1. Sem a constante `FLASHSITE_CRIADOR`, o site fica igual à 3.6.0.

## Testes
- 47 grupos ok, `tests/smoke-boot.php`: BOOT OK. PHP 8.2.27.
- demo-imobiliario (06/11, instalada por SSH): o administrador de teste vê só o aviso "Acesso administrativo" do Core; sem avisos de dependências, sem MCP Adapter, sem FULL. O Criador continua a ver tudo.
