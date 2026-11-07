/* FlashSite Core · filtro da carta: esconde pratos que não servem (classes fs-dieta-* e fs-alerg-* no item). */
(function () {
	'use strict';
	document.querySelectorAll('[data-fs-filtro]').forEach(function (box) {
		var chips = box.querySelectorAll('[data-dieta]'), sem = box.querySelector('[data-sem]'), estado = box.querySelector('.fs-filtro__estado');
		function aplica() {
			var dietas = [].filter.call(chips, function (c) { return c.getAttribute('aria-pressed') === 'true'; }).map(function (c) { return c.dataset.dieta; });
			var alerg = sem ? sem.value : '';
			var itens = document.querySelectorAll('.wp-block-post.type-fs_prato'), vis = 0;
			itens.forEach(function (li) {
				var ok = dietas.every(function (d) { return li.classList.contains('fs-dieta-' + d); }) && (!alerg || !li.classList.contains('fs-alerg-' + alerg));
				li.hidden = !ok; if (ok) vis++;
			});
			estado.textContent = (dietas.length || alerg) ? (vis ? vis + (vis === 1 ? ' prato serve' : ' pratos servem') + ' (em todas as categorias).' : 'Nenhum prato serve estes filtros.') : '';
		}
		chips.forEach(function (c) { c.addEventListener('click', function () { c.setAttribute('aria-pressed', c.getAttribute('aria-pressed') === 'true' ? 'false' : 'true'); aplica(); }); });
		if (sem) sem.addEventListener('change', aplica);
	});
})();
