/* FlashSite Core 2.6.0 · Efeitos reutilizáveis ligados por classe (o MCP só aplica a classe).
   .fs-marquee  faixa rolante em loop infinito: os filhos passam devagar da direita para a esquerda,
                param ao passar o rato e ficam parados com "reduzir movimento".
                Velocidade: ~40 px/s (variável CSS --fs-marquee-speed na própria faixa). */
(function () {
    'use strict';

    function marquee(el) {
        if (el.getAttribute('data-fs-marquee')) { return; }
        el.setAttribute('data-fs-marquee', '1');
        var items = Array.prototype.slice.call(el.children);
        if (!items.length) { return; }

        var cs = window.getComputedStyle(el);
        var gap = parseFloat(cs.columnGap) || 28;
        el.style.setProperty('--fs-marquee-gap', gap + 'px');

        var group = document.createElement('div');
        group.className = 'fs-marquee__group';
        items.forEach(function (item) { group.appendChild(item); });

        // Padrão palavra · separador · palavra: fecha o ciclo com um separador.
        if (items.length >= 3 && items.length % 2 === 1 && (items[1].textContent || '').trim().length <= 2) {
            group.appendChild(items[1].cloneNode(true));
        }

        var track = document.createElement('div');
        track.className = 'fs-marquee__track';
        track.appendChild(group);
        el.appendChild(track);

        // Repete o conteúdo até o grupo ser mais largo do que a faixa (sem buracos no loop).
        var base = Array.prototype.slice.call(group.children);
        var guard = 0;
        while (group.getBoundingClientRect().width < el.getBoundingClientRect().width && guard < 10) {
            base.forEach(function (node) {
                var copy = node.cloneNode(true);
                copy.setAttribute('aria-hidden', 'true');
                group.appendChild(copy);
            });
            guard++;
        }

        var clone = group.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        track.appendChild(clone);

        var speed = parseFloat(cs.getPropertyValue('--fs-marquee-speed')) || 40;
        var width = group.getBoundingClientRect().width;
        el.style.setProperty('--fs-marquee-duration', Math.max(8, width / speed).toFixed(1) + 's');
        el.classList.add('fs-marquee--ready');
    }

    function init() {
        Array.prototype.forEach.call(document.querySelectorAll('.fs-marquee'), marquee);
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
