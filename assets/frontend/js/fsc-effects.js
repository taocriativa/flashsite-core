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

/* FlashSite Core 3.1.0 · .fs-contador: o número do texto conta de 0 até o valor quando aparece na tela.
   Mantém o que vem antes e depois ("+500.000", "100 mil+", "98%"). Separador de milhar "." ou ",".
   Com "reduzir movimento" o número fica fixo. Duração: --fs-contador-duracao (ms) no elemento, padrão 2000. */
(function () {
    'use strict';
    var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function setup(el) {
        if (el.getAttribute('data-fs-contador')) { return; }
        el.setAttribute('data-fs-contador', '1');
        var text = el.textContent || '';
        var m = text.match(/^(\D*?)(\d{1,3}(?:[.,]\d{3})+|\d+)(.*)$/s);
        if (!m || still) { return; }
        var sep = (m[2].match(/[.,]/) || [''])[0];
        var target = parseInt(m[2].replace(/[.,]/g, ''), 10);
        if (!target) { return; }
        var fmt = function (n) {
            var s = String(n);
            return sep ? s.replace(/\B(?=(\d{3})+(?!\d))/g, sep) : s;
        };
        var dur = parseFloat(getComputedStyle(el).getPropertyValue('--fs-contador-duracao')) || 2000;
        var node = el;
        while (node.firstElementChild && node.children.length === 1) { node = node.firstElementChild; }
        var write = function (n) { node.textContent = m[1] + fmt(n) + m[3]; };
        write(0);
        var run = function () {
            var t0 = performance.now();
            var step = function (now) {
                var p = Math.min(1, (now - t0) / dur);
                write(Math.round(target * (1 - Math.pow(1 - p, 3))));
                if (p < 1) { requestAnimationFrame(step); }
            };
            requestAnimationFrame(step);
        };
        if (!('IntersectionObserver' in window)) { write(target); return; }
        var io = new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting) { io.disconnect(); run(); }
        }, { threshold: 0.4 });
        io.observe(el);
    }

    function init() { Array.prototype.forEach.call(document.querySelectorAll('.fs-contador'), setup); }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
