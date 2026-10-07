# 3.0.0-beta.4

- **WhatsApp com mensagem pronta:** a tag "FlashSite: WhatsApp (Link)" ganhou o campo "Mensagem pronta". O link passa a abrir o WhatsApp com o texto escrito (`?text=`). Aceita `{titulo}`, `{referencia}` e `{link}` da página ou do item atual (ex.: "Olá! Vi o imóvel {referencia} no site e quero agendar uma visita.").
- O número continua a vir dos Dados do negócio: nenhum botão precisa de ter o número escrito à mão.
- **Fontes com número no nome (ex.: "Source Sans 3"):** a variável de fonte do Elementor precisa de aspas para o CSS ser válido, mas com aspas o Elementor deixava de carregar a fonte e o texto caía na fonte do sistema. O Core agora pede ao Elementor que carregue as fontes das variáveis escritas com aspas (Google ou local, conforme o painel).
