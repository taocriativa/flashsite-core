# Regression Checklist 1.5.0

- [ ] instalar 1.5.0 sobre a base existente sem criar plugin duplicado
- [ ] confirmar que existe apenas uma entrada `FlashSite Core` em Plugins
- [ ] desativar e reativar o plugin sem perder `flashsite_business_profile`
- [ ] excluir o plugin com `flashsite_allow_data_deletion = false` e confirmar que os dados permanecem na base
- [ ] criar backup manual na página Segurança de Dados
- [ ] alterar dados do negócio e confirmar criação de backup automático
- [ ] restaurar último backup e confirmar retorno do perfil anterior
- [ ] ativar a opção de purga e confirmar que só então o uninstall remove as options `flashsite_*`
