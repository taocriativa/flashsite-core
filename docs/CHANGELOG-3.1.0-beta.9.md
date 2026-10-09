# 3.1.0-beta.9

- **Textos do site com tamanho previsto:** cada campo mostra o contador (ex.: 42/70). O tamanho vem da tag ("Tamanho máximo") ou, vazio, do texto padrão + 30%. Acima do previsto, aviso discreto em vermelho; com 20% de folga o campo trava. Ao salvar um texto acima do previsto, aviso para conferir a página no site.
- **Aviso de acesso administrativo:** em sites com usuário Cliente, o administrador vê nas telas sensíveis (Painel, Plugins, Temas, Usuários, Configurações) que alterações ali podem comprometer o site. Filtro `flashsite/admin_warning` desliga.
