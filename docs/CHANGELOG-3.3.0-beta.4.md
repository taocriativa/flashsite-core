# 3.3.0-beta.4

- **Correção:** salvar os Dados do negócio sem alteração devolvia erro 500. O `update_option()` do WordPress devolve `false` quando o valor já é igual ao gravado, e a API tratava isso como falha. `OptionsStorage::update()` agora relê o valor persistido quando recebe `false` e só mantém `false` se ele continuar diferente do desejado (falha real de gravação). Vale para todas as gravações que passam por `OptionsStorage`.
