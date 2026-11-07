/* FlashSite Core · "Aberto agora": calcula no navegador, no fuso do site. */
(function () {
	'use strict';
	var DIAS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
	function hora(min) { min = min % 1440; var h = Math.floor(min / 60), m = min % 60; return h + 'h' + (m < 10 ? '0' : '') + m; }
	function agoraNoFuso(fuso) {
		try {
			var p = new Intl.DateTimeFormat('en-GB', { timeZone: fuso || undefined, weekday: 'long', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).formatToParts(new Date());
			var o = {}; p.forEach(function (x) { o[x.type] = x.value; });
			return { dia: o.weekday.toLowerCase(), min: parseInt(o.hour, 10) * 60 + parseInt(o.minute, 10) };
		} catch (e) { var d = new Date(); return { dia: DIAS[d.getDay()], min: d.getHours() * 60 + d.getMinutes() }; }
	}
	document.querySelectorAll('.fs-aberto[data-horario]').forEach(function (el) {
		var semana; try { semana = JSON.parse(el.dataset.horario); } catch (e) { return; }
		var a = agoraNoFuso(el.dataset.fuso), i = DIAS.indexOf(a.dia), texto = '', aberto = false;
		var ontem = semana[DIAS[(i + 6) % 7]] || [];
		ontem.forEach(function (r) { if (r[1] > 1440 && a.min < r[1] - 1440) { aberto = true; texto = 'Aberto agora · fecha às ' + hora(r[1]); } });
		(semana[a.dia] || []).forEach(function (r) { if (!aberto && a.min >= r[0] && a.min < r[1]) { aberto = true; texto = 'Aberto agora · fecha às ' + hora(r[1]); } });
		if (!aberto) {
			var hoje = (semana[a.dia] || []).filter(function (r) { return r[0] > a.min; })[0];
			if (hoje) texto = 'Fechado · abre hoje às ' + hora(hoje[0]);
			else for (var k = 1; k <= 7; k++) {
				var d = DIAS[(i + k) % 7], r = (semana[d] || [])[0];
				if (r) { texto = 'Fechado · abre ' + (k === 1 ? 'amanhã' : ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'][(i + k) % 7]) + ' às ' + hora(r[0]); break; }
			}
		}
		if (!texto) return;
		el.querySelector('.fs-aberto__texto').textContent = texto;
		el.classList.toggle('is-aberto', aberto);
		el.hidden = false;
	});
})();
