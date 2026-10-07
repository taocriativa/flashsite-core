/**
 * FlashSite Core · Mercados do site (@since 3.0.0)
 *
 * Sugere a versão do outro país (Portugal / Brasil) sem nunca redirecionar.
 * Ordem: página atual (URL) > escolha guardada (cookie) > navegador (fuso horário, depois idioma).
 * Corre só no navegador: o HTML da página é igual para todos e pode ficar em cache.
 */
(function () {
	'use strict';
	var cfg = window.fscMarkets;
	if (!cfg || !cfg.market || !cfg.urls) {
		return;
	}
	var current = cfg.market;
	var other = current === 'PT' ? 'BR' : 'PT';
	var TZ = {
		BR: ['America/Sao_Paulo', 'America/Bahia', 'America/Fortaleza', 'America/Recife', 'America/Belem', 'America/Maceio',
			'America/Araguaina', 'America/Santarem', 'America/Manaus', 'America/Cuiaba', 'America/Campo_Grande',
			'America/Porto_Velho', 'America/Boa_Vista', 'America/Rio_Branco', 'America/Eirunepe', 'America/Noronha'],
		PT: ['Europe/Lisbon', 'Atlantic/Madeira', 'Atlantic/Azores']
	};

	function getCookie() {
		var m = document.cookie.match(new RegExp('(?:^|; )' + cfg.cookie + '=(PT|BR)'));
		return m ? m[1] : '';
	}
	function setCookie(market) {
		var secure = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = cfg.cookie + '=' + market + '; Max-Age=' + (180 * 86400) + '; Path=/; SameSite=Lax' + secure;
	}
	function track(event, params) {
		if (!Array.isArray(window.dataLayer)) {
			return;
		}
		var data = { event: event, current_market: current };
		for (var k in params) {
			if (Object.prototype.hasOwnProperty.call(params, k)) {
				data[k] = params[k];
			}
		}
		window.dataLayer.push(data);
	}
	function detect() {
		var tz = '';
		try {
			tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
		} catch (e) { /* navegador antigo */ }
		if (TZ.BR.indexOf(tz) !== -1) { return 'BR'; }
		if (TZ.PT.indexOf(tz) !== -1) { return 'PT'; }
		var langs = (navigator.languages && navigator.languages.length ? navigator.languages : [navigator.language || '']);
		for (var i = 0; i < langs.length; i++) {
			var l = String(langs[i]).toLowerCase();
			if (l === 'pt-br') { return 'BR'; }
			if (l === 'pt-pt') { return 'PT'; }
		}
		return '';
	}
	function sameUrl(a, b) {
		try {
			var x = new URL(a, location.href), y = new URL(b, location.href);
			return x.host === y.host && x.pathname.replace(/\/+$/, '') === y.pathname.replace(/\/+$/, '');
		} catch (e) {
			return false;
		}
	}

	// Cliques no seletor ou em links para a versão de um mercado guardam a escolha.
	document.addEventListener('click', function (ev) {
		var a = ev.target && ev.target.closest ? ev.target.closest('a[href]') : null;
		if (!a) { return; }
		var market = a.getAttribute('data-fs-market') || '';
		if (!market) {
			if (sameUrl(a.href, cfg.urls.PT)) { market = 'PT'; }
			else if (sameUrl(a.href, cfg.urls.BR)) { market = 'BR'; }
		}
		if (market !== 'PT' && market !== 'BR') { return; }
		setCookie(market);
		if (market !== current) {
			track('region_switch', { selected_market: market, selection_source: a.closest('.fs-market-switch') ? 'header' : 'link' });
		}
	}, true);

	if (!cfg.prompt || getCookie()) {
		return;
	}
	var detected = detect();
	if (detected !== other || !cfg.urls[other]) {
		return;
	}
	try {
		if (sessionStorage.getItem('fscMarketsClosed') === '1') { return; }
	} catch (e) { /* sem sessionStorage */ }

	var t = cfg.texts || {};
	var css = '.fsc-market{position:fixed;left:16px;right:16px;bottom:16px;z-index:2147483000;max-width:460px;margin-inline:auto;'
		+ 'background:var(--fs-cor-fundo,#fff);color:var(--fs-cor-texto,#111);border:1px solid var(--fs-cor-linha,rgba(0,0,0,.12));'
		+ 'border-radius:14px;box-shadow:0 18px 50px -12px rgba(0,0,0,.35);padding:16px 18px;font:15px/1.45 var(--fs-fonte-texto,system-ui,sans-serif);'
		+ 'transform:translateY(20px);opacity:0;transition:transform .35s ease,opacity .35s ease}'
		+ '.fsc-market.is-top{top:16px;bottom:auto;transform:translateY(-20px)}'
		+ '.fsc-market.is-in{transform:none;opacity:1}'
		+ '.fsc-market__t{font-weight:700;font-size:16px;margin:0 0 2px}.fsc-market__p{margin:0 0 12px;opacity:.8}'
		+ '.fsc-market__a{display:flex;flex-wrap:wrap;gap:8px;align-items:center}'
		+ '.fsc-market__go{background:var(--fs-cor-destaque,#111);color:var(--fs-cor-destaque-contraste,#fff);border:0;border-radius:999px;padding:10px 18px;font:inherit;font-weight:700;cursor:pointer;text-decoration:none}'
		+ '.fsc-market__stay{background:none;border:0;color:inherit;font:inherit;text-decoration:underline;cursor:pointer;padding:10px 6px}'
		+ '.fsc-market__go:focus-visible,.fsc-market__stay:focus-visible{outline:2px solid currentColor;outline-offset:2px}'
		+ '@media (prefers-reduced-motion:reduce){.fsc-market{transition:none}}';
	var style = document.createElement('style');
	style.textContent = css;
	document.head.appendChild(style);

	var box = document.createElement('div');
	box.className = 'fsc-market';
	box.setAttribute('role', 'region');
	box.setAttribute('aria-label', t.title || '');
	box.innerHTML = '<p class="fsc-market__t"></p><p class="fsc-market__p"></p><div class="fsc-market__a"><a class="fsc-market__go"></a><button type="button" class="fsc-market__stay"></button></div>';
	box.querySelector('.fsc-market__t').textContent = (t.flag ? t.flag + ' ' : '') + (t.title || '');
	box.querySelector('.fsc-market__p').textContent = t.text || '';
	var go = box.querySelector('.fsc-market__go');
	go.textContent = t.go || '';
	go.href = cfg.urls[other];
	go.setAttribute('hreflang', other === 'BR' ? 'pt-BR' : 'pt-PT');
	go.addEventListener('click', function () {
		setCookie(other);
		track('region_switch', { detected_country: detected, selected_market: other, selection_source: 'prompt' });
	});
	var stay = box.querySelector('.fsc-market__stay');
	stay.textContent = t.stay || '';
	stay.addEventListener('click', function () {
		setCookie(current);
		track('region_selected', { detected_country: detected, selected_market: current, selection_source: 'prompt' });
		box.classList.remove('is-in');
		setTimeout(function () { box.remove(); }, 400);
	});
	document.addEventListener('keydown', function (ev) {
		if (ev.key === 'Escape' && document.body.contains(box)) {
			try { sessionStorage.setItem('fscMarketsClosed', '1'); } catch (e) { /* ignora */ }
			box.remove();
		}
	});

	// Outra barra fixa em baixo (ex.: aviso de cookies)? Então o aviso vai para o topo.
	function bottomIsTaken() {
		var el = document.elementFromPoint(window.innerWidth / 2, window.innerHeight - 24);
		while (el && el !== document.body && el !== document.documentElement) {
			var pos = getComputedStyle(el).position;
			if (pos === 'fixed' || pos === 'sticky') { return true; }
			el = el.parentElement;
		}
		return false;
	}
	function show() {
		if (bottomIsTaken()) { box.classList.add('is-top'); }
		document.body.appendChild(box);
		requestAnimationFrame(function () { box.classList.add('is-in'); });
		track('region_prompt_shown', { detected_country: detected });
	}
	// Depois do primeiro ecrã (e de banners de cookies que abrem logo).
	setTimeout(show, 2500);
})();
