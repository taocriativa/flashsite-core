# 3.1.0-beta.15

- **Fundo molecular (`fs-moleculas`):** classe global que põe atrás do conteúdo do bloco um fundo animado em WebGL (células, membranas e partículas). Cores pelas variáveis `--fs-mol-fundo-1`, `--fs-mol-fundo-2`, `--fs-mol-cor-1`, `--fs-mol-cor-2`. Começa depois do carregamento (não atrasa a página), para fora do ecrã e fica parado com "reduzir movimento". Substitui o widget HTML com shader.
- **Topo fixo (`fs-topo-fixo`):** recebe `is-rolado` depois de 40 px de rolagem, para o topo transparente ganhar fundo (`&.is-rolado { … }` na classe).
