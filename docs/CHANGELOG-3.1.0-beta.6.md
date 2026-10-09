# 3.1.0-beta.6

Painel do cliente.

- **Textos do site** (FlashSite › Textos do site): o cliente muda títulos e textos das páginas num formulário, sem abrir o Elementor.
  - Quem monta o site marca cada texto com a tag dinâmica **Texto do site (editável pelo cliente)**: chave, nome do campo, parte da página, tipo (uma linha ou parágrafo) e texto padrão.
  - A lista de campos é lida das páginas quando são salvas no Elementor, quando o Core muda de versão e pelo link "atualize a lista" (só administradores).
  - Campo vazio volta ao texto padrão. Ao guardar, limpa o cache das páginas onde o texto aparece (página a página, nunca o cache inteiro).
- **O que pode editar**: nova primeira tela do painel, com atalhos para cada coleção (adicionar e ver todos, com contagem), Textos do site, Dados do negócio, WhatsApp e formulários, Política de privacidade, Fotos e a conta.
- **Perfil Cliente** (o antigo "Gestor de Site"):
  - entra direto em "O que pode editar" (sem o Painel do WordPress);
  - não vê Comentários, Ferramentas, as telas técnicas do FlashSite (Visão Geral, Short codes, Setup Wizard, Segurança de Dados, Mercados, Coleções), os avisos de plugins nem os atalhos de plugins na barra superior;
  - continua a poder subir fotos.
- **Painel em PT-BR nos sites do Brasil**: as telas do FlashSite e das coleções trocam as palavras de Portugal pelas do Brasil (salvar, arquivo, endereço, CEP, contato, celular, senha…).
