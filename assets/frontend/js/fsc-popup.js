/* FlashSite Core 2.6.0 · Popups do site ("Modelo · Popup" / "Modelo · Popup saída").
   Gatilhos: N segundos de navegação (somados entre páginas na mesma visita) e intenção de
   saída (rato a sair pelo topo, só em computador). Um botão com link "#fechar" fecha o popup. No máximo um popup por visita; depois de
   fechado não volta durante "cooldown" dias. Teste: ?fs_popup=timer ou ?fs_popup=exit. */
(function () {
    'use strict';
    var root = document.querySelector('.fs-popups');
    if (!root) { return; }

    var delay = parseInt(root.getAttribute('data-delay'), 10) || 0;
    var cooldownDays = parseInt(root.getAttribute('data-cooldown'), 10) || 0;
    var version = root.getAttribute('data-version') || '';
    var KEY_CLOSED = 'fs_popup_closed';
    var KEY_SHOWN = 'fs_popup_shown';
    var KEY_START = 'fs_popup_start';

    function store(type) {
        try { return window[type]; } catch (e) { return null; }
    }
    var local = store('localStorage');
    var session = store('sessionStorage');
    function get(s, k) { try { return s ? s.getItem(k) : null; } catch (e) { return null; } }
    function set(s, k, v) { try { if (s) { s.setItem(k, v); } } catch (e) {} }

    function dialogFor(trigger) {
        var el = root.querySelector('dialog[data-trigger="' + trigger + '"]');
        if (el) { return el; }
        var alias = root.querySelector('[data-fs-popup-alias="' + trigger + '"]');
        return alias ? document.getElementById(alias.getAttribute('data-target')) : null;
    }

    var forced = (new URLSearchParams(window.location.search)).get('fs_popup');
    var open = null;

    function blocked() {
        if (get(session, KEY_SHOWN)) { return true; }
        var closed = get(local, KEY_CLOSED);
        if (!closed) { return false; }
        var parts = closed.split('|');
        var at = parseInt(parts[0], 10) || 0;
        return parts[1] === version && Date.now() - at < cooldownDays * 86400000;
    }

    function show(trigger, force) {
        if (open || (!force && blocked())) { return; }
        var dialog = dialogFor(trigger);
        if (!dialog) { return; }
        open = dialog;
        set(session, KEY_SHOWN, '1');
        if (typeof dialog.showModal === 'function') { dialog.showModal(); } else { dialog.setAttribute('open', ''); }
        document.documentElement.classList.add('fs-popup-open');
    }

    function close() {
        if (!open) { return; }
        if (typeof open.close === 'function' && open.open) { open.close(); } else { open.removeAttribute('open'); }
        open = null;
        document.documentElement.classList.remove('fs-popup-open');
        set(local, KEY_CLOSED, Date.now() + '|' + version);
    }

    Array.prototype.forEach.call(root.querySelectorAll('dialog.fs-popup'), function (dialog) {
        dialog.addEventListener('cancel', function (e) { e.preventDefault(); close(); });
        dialog.addEventListener('click', function (e) {
            var closer = e.target.closest('[data-fs-popup-close], a[href$="#fechar"]');
            if (e.target === dialog || closer) { e.preventDefault(); close(); return; }
            if (e.target.closest('a[href]')) { set(local, KEY_CLOSED, Date.now() + '|' + version); }
        });
    });

    if (forced === 'timer' || forced === 'exit' || forced === '1') {
        window.setTimeout(function () { show(forced === 'exit' ? 'exit' : 'timer', true); }, 600);
        return;
    }
    if (blocked()) { return; }

    // Tempo de navegação somado na visita (sessionStorage), não só nesta página.
    var start = parseInt(get(session, KEY_START), 10);
    if (!start) { start = Date.now(); set(session, KEY_START, String(start)); }
    window.setTimeout(function () { show('timer', false); }, Math.max(0, delay * 1000 - (Date.now() - start)));

    // Intenção de saída: só com rato (em telemóvel não há sinal fiável), após 5 s na página.
    if (window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        window.setTimeout(function () {
            document.addEventListener('mouseout', function (e) {
                if (!e.relatedTarget && e.clientY <= 0) { show('exit', false); }
            });
        }, 5000);
    }
})();
