# 3.1.0-beta.4

- **Galeria mais leve na página do item:** as fotos 2 em diante da galeria (miniaturas) passam a carregar só quando chegam à tela e no tamanho em que aparecem (`loading="lazy"` e `sizes`). Antes, o navegador baixava a foto inteira para cada miniatura. A capa e a 1.ª foto não mudam.
- **Filtro de localização sem travessão:** bairros dentro de uma cidade aparecem recuados por espaço, não por "—".
