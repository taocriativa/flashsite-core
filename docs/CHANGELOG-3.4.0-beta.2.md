# FlashSite Core 3.4.0-beta.2 · Onda 1 do catálogo e `schema_map`

Base: 3.4.0-beta.1 (commit aedcab5). Beta de teste: só em sites de demonstração. Nenhuma Collection nova está ativa por padrão.

Contrato completo em `COLLECTIONS-CONTRACT.md` (seções 6 e 7).

## Quatro presets oficiais (desativados por padrão)
- **`servico`** (`/servicos/`, schema Service): atividade ou oferta profissional, com preço opcional. Distinto de `plano`, que continua como era.
- **`equipa`** (`/equipa/`, `/equipe/` no Brasil, schema Person): várias pessoas de uma organização. Foto = imagem de capa, biografia = editor.
- **`projeto`** (`/projetos/`, schema CreativeWork): portfólio, casos, obras. Capa pela primeira foto da galeria.
- **`evento`** (`/eventos/`, schema Event): próximos e passados pela primitiva temporal da beta.1 (`upcoming | past | all`), com ordem declarada para cada janela. Estado fixo (confirmado, esgotado, adiado, cancelado). Evento passado continua publicado. Sem recorrência, reservas nem calendário.
- Campos públicos e privados, taxonomias e settings de cada um: contrato, seção 7.

## `schema_map`
- Mapeia propriedades Schema.org para campos públicos do preset (texto, data, número) ou para o valor fixo de um termo, sem condicionais por preset.
- Opcional. Sem ele, o JSON-LD de `imovel`, `plano` e `prato` é igual ao do 3.3.0 (`tests/fixtures/golden/jsonld-3.3.0.json`, gerado a partir da `main`).
- Sem objetos aninhados, listas nem transformações. Campo privado nunca aparece.

## Traduções
- Textos dos quatro presets (arquivos, taxonomias, termos) em EN, ES e FR. A camada BR cobre `equipe`, "Registro profissional" e "Contatos". Teste confere que nenhum texto fica sem tradução.

## Itens de exemplo
- `flashsite-demo-kit` (plugin separado, fora deste repositório): kits `servico`, `equipa`, `projeto` e `evento` (os eventos usam datas relativas a hoje, para haver próximos e passados). Versão e ZIP do demo-kit ficam para decisão do Diretor.

## Limitações conhecidas (registradas, não bloqueiam a beta.2)
- `schema_map` é propositalmente simples (propriedade → campo público, ou propriedade → termo com valor fixo). `Event.location` sai como texto, não como `Place`; `startDate` e `endDate` são só a data (sem hora); a equipa ainda não gera `sameAs` com várias redes. Estruturas aninhadas e listas ficam fora do `schema_map` e, se forem necessárias, no kit do job.
- Dívida de organização: o `flashsite-demo-kit` não tem repositório Git próprio.

## O que não muda
- `imovel`, `plano` e `prato`: nenhum arquivo alterado. Resumos `sha1` dos presets (PT e BR) e JSON-LD iguais ao 3.3.0.
- Instalar a beta.2 não altera nenhum site que não ative os novos presets. Efeito conhecido, já da beta.1: cada site regrava uma vez as capabilities dos papéis.

## Qualidade
- 39 grupos de teste (novos: `OndaUmPresetsTest`, `SchemaMapTest`) e `tests/smoke-boot.php`.
