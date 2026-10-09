# 3.1.0-beta.14

- **Mapa dos Dados do negócio corrigido:** o campo "Google Maps (embed)" apagava o código ao gravar (o filtro do WordPress remove iframes) e ficava vazio. Agora aceita o código copiado do Google Maps (Partilhar › Incorporar) ou o endereço do embed, guarda só o endereço (apenas google.com/maps/embed) e o Core monta o mapa. Vale para `[flashsite_maps]` e para a classe `fs-mapa`.
