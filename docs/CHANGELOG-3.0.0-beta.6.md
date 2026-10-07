# 3.0.0-beta.6

- **Mercados do site (FlashSite › Mercados do site):** a mesma página em versão Portugal e versão Brasil (ex.: flashsite.pt/ e flashsite.pt/br/), sem plugin de tradução.
  - Até 5 pares de páginas. Cada página do par recebe `hreflang` pt-PT, pt-BR e x-default (versão principal escolhida no painel). Só com as duas páginas publicadas.
  - Seletor de país: shortcode `[flashsite_market_switch]` (🇵🇹 Portugal · 🇧🇷 Brasil), herda cor e fonte do sítio onde é posto.
  - Aviso discreto para quem chega do outro país: o navegador indica o país pelo fuso horário e, se não der, pelo idioma (pt-BR / pt-PT). Não usa IP nem plugin de GeoIP. Nunca redireciona. Feito em JavaScript, por isso o HTML em cache é igual para todos.
  - Escolha guardada no cookie `flashsite_region` (PT ou BR, 180 dias), com prioridade sobre a deteção. Cliques no seletor ou em links para a outra versão também guardam a escolha.
  - Eventos no dataLayer (se existir): `region_prompt_shown`, `region_switch`, `region_selected`.
- Testes: MarketsModuleTest (pares e hreflang). Script testado no Chrome com fuso da Bahia, de Lisboa e de Berlim, idioma pt-BR e escolha já guardada.
