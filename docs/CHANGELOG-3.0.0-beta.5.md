# 3.0.0-beta.5

- Correção do beta.4: as fontes de variáveis com aspas não carregavam porque o Elementor guarda o valor da variável como objeto (`{"$$type", "value"}`). O Core passa a ler os dois formatos.
