/**
 * FlashSite Core · Abas (e-tabs) pelo endereço e pelo país (@since 3.1.0)
 *
 * 1. Endereço com âncora (ex.: /termos-e-condicoes/#brasil): abre a aba cujo rótulo, sem acentos
 *    nem bandeira, é igual à âncora ("Brasil" → #brasil). Vale para qualquer e-tabs do site.
 * 2. Sem âncora, em abas com "Portugal" e "Brasil": abre a do país do visitante (escolha guardada
 *    no seletor de país, depois fuso horário). Nunca muda de página.
 */
(function () {
	'use strict';
	var BR_TZ = /^America\/(Sao_Paulo|Bahia|Fortaleza|Recife|Belem|Maceio|Araguaina|Santarem|Manaus|Cuiaba|Campo_Grande|Porto_Velho|Boa_Vista|Rio_Branco|Eirunepe|Noronha)$/;

	function norm(s) {
		s = String(s || '').toLowerCase();
		if (s.normalize) { s = s.normalize('NFD').replace(/[̀-ͯ]/g, ''); }
		return s.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
	}
	function tabs() {
		return Array.prototype.slice.call(document.querySelectorAll('[data-element_type="e-tab"], [role="tab"]'));
	}
	function open(name) {
		var key = norm(name);
		if (!key) { return false; }
		var list = tabs();
		for (var i = 0; i < list.length; i++) {
			if (norm(list[i].textContent) === key) {
				if (list[i].getAttribute('aria-selected') !== 'true') { list[i].click(); }
				return true;
			}
		}
		return false;
	}
	function visitorMarket() {
		var m = document.cookie.match(/(?:^|; )flashsite_region=(PT|BR)/);
		if (m) { return m[1]; }
		try {
			var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
			if (BR_TZ.test(tz)) { return 'BR'; }
		} catch (e) { /* navegador antigo */ }
		return '';
	}
	function run() {
		if (location.hash.length > 1 && open(decodeURIComponent(location.hash.slice(1)))) { return; }
		var names = tabs().map(function (t) { return norm(t.textContent); });
		if (names.indexOf('portugal') !== -1 && names.indexOf('brasil') !== -1 && visitorMarket() === 'BR') {
			open('brasil');
		}
	}
	// As abas do Elementor ligam-se depois do carregamento: tentar já e outra vez a seguir.
	function start() { run(); setTimeout(run, 400); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
	window.addEventListener('hashchange', function () { open(decodeURIComponent(location.hash.slice(1))); });
})();
