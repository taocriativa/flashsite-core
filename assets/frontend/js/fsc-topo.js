/* FlashSite Core 3.1.0 · Topo fixo ligado por classe.
   .fs-topo-fixo   ganha a classe is-rolado depois de 40 px de rolagem (fundo sólido num topo transparente). */
(function () {
    'use strict';

    function topo() {
        var els = document.querySelectorAll('.fs-topo-fixo');
        if (!els.length) { return; }
        var on = function () {
            var r = window.scrollY > 40;
            Array.prototype.forEach.call(els, function (e) { e.classList.toggle('is-rolado', r); });
        };
        window.addEventListener('scroll', on, { passive: true });
        on();
    }

    function init() {
        topo();
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
