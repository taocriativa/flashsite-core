/* FlashSite Core 2.6.0 · Popups do site (páginas com papel "Popup").
   Cada popup tem o seu gatilho e a sua frequência (data-*), definidos nas Definições da página:
   - delay:  N segundos de navegação, somados entre páginas na mesma visita;
   - exit:   rato a sair pelo topo da janela (só computador);
   - scroll: N% da página percorrida.
   Frequência: "session" uma vez por visita; "days" uma vez por visita e, depois de fechado,
   só volta após N dias (editar o popup reinicia); "always" em todas as páginas.
   Nunca abrem dois ao mesmo tempo: o seguinte espera que o anterior feche.
   Um botão com link "#fechar" fecha o popup. Teste: ?fs_popup=<ID da página>, ?fs_popup=delay|exit|scroll. */
(function () {
    'use strict';
    var root = document.querySelector('.fs-popups');
    if (!root) { return; }

    function store(type) { try { return window[type]; } catch (e) { return null; } }
    var local = store('localStorage');
    var session = store('sessionStorage');
    function get(s, k) { try { return s ? s.getItem(k) : null; } catch (e) { return null; } }
    function set(s, k, v) { try { if (s) { s.setItem(k, v); } } catch (e) {} }

    var KEY_START = 'fs_popup_start';
    var isDesktop = !!(window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches);
    var dialogs = Array.prototype.slice.call(root.querySelectorAll('dialog.fs-popup'));
    var open = null;
    var queue = [];

    function cfg(d) {
        return {
            id: d.getAttribute('data-popup'),
            trigger: d.getAttribute('data-trigger') || 'delay',
            delay: parseInt(d.getAttribute('data-delay'), 10) || 0,
            scroll: parseInt(d.getAttribute('data-scroll'), 10) || 50,
            frequency: d.getAttribute('data-frequency') || 'days',
            days: parseInt(d.getAttribute('data-days'), 10) || 0,
            device: d.getAttribute('data-device') || 'all',
            version: d.getAttribute('data-version') || ''
        };
    }

    function blocked(c) {
        if (c.frequency === 'always') { return false; }
        if (get(session, 'fs_popup_seen_' + c.id)) { return true; }
        if (c.frequency !== 'days') { return false; }
        var closed = (get(local, 'fs_popup_closed_' + c.id) || '').split('|');
        var at = parseInt(closed[0], 10) || 0;
        return closed[1] === c.version && Date.now() - at < c.days * 86400000;
    }

    function show(d, force) {
        var c = cfg(d);
        if (d.open || (!force && blocked(c))) { return; }
        if (open) {
            if (c.trigger !== 'exit' && queue.indexOf(d) === -1) { queue.push(d); }
            return;
        }
        open = d;
        set(session, 'fs_popup_seen_' + c.id, '1');
        if (typeof d.showModal === 'function') { d.showModal(); } else { d.setAttribute('open', ''); }
        // O foco vai para a caixa, não para o X (evita o anel de foco ao abrir).
        if (!d.hasAttribute('tabindex')) { d.setAttribute('tabindex', '-1'); }
        try { d.focus({ preventScroll: true }); } catch (err) { d.focus(); }
        document.documentElement.classList.add('fs-popup-open');
    }

    function remember(d) {
        var c = cfg(d);
        set(local, 'fs_popup_closed_' + c.id, Date.now() + '|' + c.version);
    }

    function close(d) {
        if (typeof d.close === 'function' && d.open) { d.close(); } else { d.removeAttribute('open'); }
        remember(d);
        if (open === d) { open = null; }
        document.documentElement.classList.remove('fs-popup-open');
        var next = queue.shift();
        if (next) { window.setTimeout(function () { show(next, false); }, 800); }
    }

    dialogs.forEach(function (d) {
        d.addEventListener('cancel', function (e) { e.preventDefault(); close(d); });
        d.addEventListener('click', function (e) {
            var closer = e.target.closest('[data-fs-popup-close], a[href$="#fechar"]');
            if (e.target === d || closer) { e.preventDefault(); close(d); return; }
            if (e.target.closest('a[href]')) { remember(d); }
        });
    });

    var forced = (new URLSearchParams(window.location.search)).get('fs_popup');
    if (forced) {
        var target = dialogs.filter(function (d) { var c = cfg(d); return c.id === forced || c.trigger === forced || (forced === 'timer' && c.trigger === 'delay'); })[0];
        if (target) { window.setTimeout(function () { show(target, true); }, 600); }
        return;
    }

    var start = parseInt(get(session, KEY_START), 10);
    if (!start) { start = Date.now(); set(session, KEY_START, String(start)); }

    var exitPopups = [];
    var scrollPopups = [];
    dialogs.forEach(function (d) {
        var c = cfg(d);
        if ((c.device === 'desktop' && !isDesktop) || (c.device === 'mobile' && isDesktop) || blocked(c)) { return; }
        if (c.trigger === 'delay') {
            window.setTimeout(function () { show(d, false); }, Math.max(0, c.delay * 1000 - (Date.now() - start)));
        } else if (c.trigger === 'exit' && isDesktop) {
            exitPopups.push(d);
        } else if (c.trigger === 'scroll') {
            scrollPopups.push(d);
        }
    });

    if (exitPopups.length) {
        window.setTimeout(function () {
            document.addEventListener('mouseout', function (e) {
                if (e.relatedTarget || e.clientY > 0) { return; }
                for (var i = 0; i < exitPopups.length; i++) {
                    if (!blocked(cfg(exitPopups[i]))) { show(exitPopups[i], false); return; }
                }
            });
        }, 5000);
    }

    if (scrollPopups.length) {
        var onScroll = function () {
            var max = document.documentElement.scrollHeight - window.innerHeight;
            var pct = max > 0 ? (window.scrollY / max) * 100 : 100;
            scrollPopups = scrollPopups.filter(function (d) {
                if (pct >= cfg(d).scroll) { show(d, false); return false; }
                return true;
            });
            if (!scrollPopups.length) { window.removeEventListener('scroll', onScroll); }
        };
        window.addEventListener('scroll', onScroll, { passive: true });
    }
})();
