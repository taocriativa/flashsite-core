/* FlashSite Core · efeitos: marca .is-in quando o bloco entra no ecrã (uma vez). */
(function () {
	'use strict';
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;
	var items = document.querySelectorAll('.fs-fx--entrada, .fs-fx--revelar');
	if (!items.length) return;
	var vh = window.innerHeight;
	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
	}, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
	items.forEach(function (el) {
		// O que já está à vista ao abrir a página não anima (não atrasa o primeiro ecrã nem o LCP).
		if (el.getBoundingClientRect().top < vh * 0.9) { el.classList.add('is-in'); return; }
		io.observe(el);
	});
	document.documentElement.classList.add('fs-fx-on');
})();
