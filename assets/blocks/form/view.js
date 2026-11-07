/* FlashSite Core · envio do bloco Formulário (sem dependências). */
(function () {
	'use strict';
	function setError(form, name, message) {
		var input = form.querySelector('[name="' + name + '"]');
		if (!input) return;
		var box = input.closest('.fs-campo');
		if (!box) return;
		var slot = box.querySelector('.fs-campo__erro');
		if (message) { box.setAttribute('data-invalido', ''); input.setAttribute('aria-invalid', 'true'); }
		else { box.removeAttribute('data-invalido'); input.removeAttribute('aria-invalid'); }
		if (slot) slot.textContent = message || '';
	}
	function init(form) {
		var started = Math.floor(Date.now() / 1000);
		var status = form.querySelector('.fs-form__estado');
		form.addEventListener('submit', function (event) {
			event.preventDefault();
			var fields = {}, firstInvalid = null;
			form.querySelectorAll('.fs-campo [name]').forEach(function (el) {
				fields[el.name] = el.value;
				var message = '';
				if (el.required && !el.value.trim()) message = 'Campo obrigatório.';
				else if (el.type === 'email' && el.value && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(el.value)) message = 'E-mail inválido.';
				else if (el.type === 'tel' && el.value && el.value.replace(/\D+/g, '').length < 9) message = 'Número de telefone inválido.';
				setError(form, el.name, message);
				if (message && !firstInvalid) firstInvalid = el;
			});
			if (firstInvalid) { firstInvalid.focus(); return; }
			form.setAttribute('aria-busy', 'true');
			status.textContent = '';
			var honeypot = form.querySelector('[name="website"]');
			fetch(form.dataset.endpoint, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ post: form.dataset.post, form: form.dataset.form, fields: fields, website: honeypot ? honeypot.value : '', t: started })
			}).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
			.then(function (res) {
				form.removeAttribute('aria-busy');
				if (res.ok && res.data.ok) {
					status.textContent = form.dataset.success;
					form.reset();
					if (res.data.whatsapp) window.open(res.data.whatsapp, '_blank', 'noopener');
					return;
				}
				if (res.data && res.data.errors) Object.keys(res.data.errors).forEach(function (n) { setError(form, n, res.data.errors[n]); });
				status.textContent = (res.data && res.data.message) || form.dataset.error;
			}).catch(function () { form.removeAttribute('aria-busy'); status.textContent = form.dataset.error; });
		});
	}
	document.querySelectorAll('form.fs-form').forEach(init);
})();
