# FlashSite Core 2.6.0-beta.1 · Coleções (beta para os sites de demonstração)

Base: 2.5.2. Versão de teste para demo-imobiliario.flashsite.pt e demo-personal-trainer.flashsite.pt.
Não instalar em sites de clientes.

## Novo: módulo Coleções
- Motor de conteúdos repetíveis por setor a partir de presets declarativos (`config/collections/*.php`).
- Preset **Imóvel** (`fs_imovel`, `/imoveis/`): finalidade, tipo, tipologia, estado, zona (hierárquica) e faixa de preço automática; 17 campos; morada e notas internas privadas.
- Ficha própria no painel (separadores, galeria com arrastar, validação em PT; com erros o item fica em rascunho).
- Página **FlashSite › Coleções** (só Administrador): ligar/desligar coleções e importar/remover exemplos fictícios.
- Permissões: o Gestor de Site cria/edita/publica itens e carrega fotos (`upload_files`); só o Administrador gere coleções (`flashsite_manage_collections`).

## Saída para o site
- Dynamic Tags (grupo "FlashSite · Coleções"): Item · Campo, Preço, Área útil, Classificação, Estado, Tipologia, Zona, Imagem, Galeria, Link.
- Preço: "285.000,00 €" (opção "Sem ,00"), "€/mês" no arrendamento, "Preço sob consulta".
- Loop Grid (Elementor Pro) › Query ID: `flashsite_featured` (destaques) e `flashsite_available` (esconde vendidos/arrendados).
- Shortcodes: `[flashsite_collection type="imovel" limit="6" featured="1" available="1" orderby="price"]` e `[flashsite_item field="preco"]` (`field="tax:zona"` para classificações).
- REST pública só de leitura: `/wp-json/flashsite/v1/public/collections[/{tipo}[/{id}]]`, só campos públicos.
- JSON-LD `RealEstateListing` com Offer e disponibilidade (via Rank Math quando ativo).

## Alterações
- Tag "E-mail Administrativo" deixa de ser registada: `contact.email_admin` é interno em todas as saídas.
- `FLASHSITE_DATA_VERSION` 2.6.0. Nenhuma coleção ativa por defeito; os sites atuais não mudam.

## Data Safety
- Desligar uma coleção ou desinstalar o plugin nunca apaga itens. Revisões guardam os campos de cada item.
