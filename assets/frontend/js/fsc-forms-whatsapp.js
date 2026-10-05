/* FlashSite Core 2.6.0 · Formulários → WhatsApp.
   Depois de um envio com sucesso, a mesma aba abre o WhatsApp do negócio com o resumo dos
   campos preenchidos ("Rótulo: valor"). Funciona com o formulário do Elementor 4 (atómico) e
   com o formulário clássico do Elementor Pro. Os campos são lidos sozinhos: não há nada a
   configurar por formulário. Navegação na aba atual, por isso nunca é bloqueada como pop-up.
   Modo "marked": só formulários com a classe fs-form-whatsapp. Classe fs-form-no-whatsapp exclui.
   Também valida os campos de telefone (país em FlashSite › WhatsApp e formulários). */
(function () {
    'use strict';
    var cfg = window.flashsiteFormsWhatsapp || {};

    /* ---------- Telefones: só números, com o comprimento do país ----------
       PT: 9 dígitos (2/3/9…); BR: DDD + 8 ou 9 dígitos; INT: 7 a 15 dígitos.
       Com indicativo (+351 / +55 / 00…), valida pelo país do indicativo. */
    var PHONE_RULES = {
        PT: { code: '351', min: 9, max: 9, re: /^[239]\d{8}$/, msg: 'Indique um número português com 9 dígitos (ex.: 912 345 678).' },
        BR: { code: '55', min: 10, max: 11, re: /^[1-9]{2}9?\d{8}$/, msg: 'Informe DDD + número, só algarismos (ex.: 71 99999 9999).' },
        INT: { code: '', min: 7, max: 15, re: /^\d{7,15}$/, msg: 'Indique um número de telefone válido.' }
    };

    function phoneError(raw) {
        var value = (raw || '').trim();
        if (value === '') { return ''; }
        if (/[^\d\s+()\-.]/.test(value)) { return 'Use só números.'; }
        var digits = value.replace(/\D/g, '');
        var intl = /^\s*(\+|00)/.test(value);
        if (intl) {
            if (value.indexOf('00') === 0) { digits = digits.slice(2); }
            if (digits.indexOf('351') === 0) { return PHONE_RULES.PT.re.test(digits.slice(3)) ? '' : PHONE_RULES.PT.msg; }
            if (digits.indexOf('55') === 0) { return PHONE_RULES.BR.re.test(digits.slice(2)) ? '' : PHONE_RULES.BR.msg; }
            return digits.length >= 8 && digits.length <= 15 ? '' : PHONE_RULES.INT.msg;
        }
        var rule = PHONE_RULES[cfg.phoneCountry] || PHONE_RULES.INT;
        return rule.re.test(digits) ? '' : rule.msg;
    }

    function isPhone(el) {
        return el && el.tagName === 'INPUT' && (el.type === 'tel' || /tel|phone|telefone|telemovel|whats|celular/i.test(el.name || el.id || ''));
    }

    function armPhone(el) {
        if (el.__fsPhone) { return; }
        el.__fsPhone = true;
        el.setAttribute('inputmode', 'tel');
        el.setAttribute('autocomplete', 'tel');
        el.setAttribute('maxlength', '20');
    }

    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!isPhone(el)) { return; }
        armPhone(el);
        var cleaned = el.value.replace(/[^\d\s+()\-.]/g, '').replace(/(?!^)\+/g, '');
        if (cleaned !== el.value) { el.value = cleaned; }
        el.setCustomValidity(phoneError(el.value));
    }, true);

    document.addEventListener('focusin', function (e) { if (isPhone(e.target)) { armPhone(e.target); } });

    // Antes de qualquer envio: bloqueia se algum telefone estiver inválido.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') { return; }
        var bad = null;
        form.querySelectorAll('input').forEach(function (el) {
            if (!isPhone(el)) { return; }
            el.setCustomValidity(phoneError(el.value));
            if (!bad && el.validationMessage) { bad = el; }
        });
        if (bad) {
            e.preventDefault();
            e.stopImmediatePropagation();
            bad.reportValidity();
        }
    }, true);

    if (!cfg.number) { return; }

    var CONSENT = /autoriz|consent|aceito|concordo|privacidade|rgpd|lgpd|termos/i;
    var captured = new WeakMap();
    var redirecting = false;

    function wanted(form) {
        if (form.classList.contains('fs-form-no-whatsapp')) { return false; }
        if (cfg.mode === 'marked') { return form.classList.contains('fs-form-whatsapp') || !!form.closest('.fs-form-whatsapp'); }
        return true;
    }

    function clean(text) {
        return (text || '').replace(/\s+/g, ' ').replace(/[\s*:]+$/, '').trim();
    }

    function labelFor(form, el) {
        var label = null;
        if (el.id) {
            try { label = form.querySelector('label[for="' + CSS.escape(el.id) + '"]'); } catch (e) { label = null; }
        }
        if (!label) {
            var group = el.closest('.elementor-field-group');
            if (group) { label = group.querySelector('label.elementor-field-label, label'); }
        }
        if (!label) {
            // Formulário atómico: o rótulo é o irmão anterior do campo.
            var prev = el.previousElementSibling;
            while (prev && prev.tagName !== 'LABEL' && !/^(INPUT|SELECT|TEXTAREA)$/.test(prev.tagName)) { prev = prev.previousElementSibling; }
            if (prev && prev.tagName === 'LABEL') { label = prev; }
        }
        if (!label && (el.type === 'checkbox' || el.type === 'radio')) {
            label = el.closest('label');
        }
        return clean(label ? label.textContent : (el.getAttribute('placeholder') || el.name || ''));
    }

    function groupLabel(form, el) {
        var group = el.closest('.elementor-field-group, fieldset, [role="radiogroup"]');
        if (group) {
            var legend = group.querySelector('legend, label.elementor-field-label');
            if (legend) { return clean(legend.textContent); }
        }
        return labelFor(form, el);
    }

    function collect(form) {
        var rows = [];
        var byName = {};
        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            var type = (el.type || '').toLowerCase();
            if (el.disabled || ['hidden', 'submit', 'button', 'reset', 'password', 'file', 'image'].indexOf(type) !== -1) { return; }
            if (el.name && /^(form_id|post_id|queried_id|referer_title|_wpnonce|g-recaptcha)/.test(el.name)) { return; }
            var label;
            var value;
            if (type === 'checkbox' || type === 'radio') {
                if (!el.checked) { return; }
                var optionLabel = el.closest('label') ? clean(el.closest('label').textContent) : '';
                var labelEl = el.id ? form.querySelector('label[for="' + el.id + '"]') : null;
                optionLabel = optionLabel || (labelEl ? clean(labelEl.textContent) : '') || el.value;
                if (CONSENT.test(optionLabel) || CONSENT.test(el.name || '')) { return; }
                label = groupLabel(form, el);
                value = (el.value && el.value !== 'on') ? (optionLabel.length < 60 ? optionLabel : el.value) : 'Sim';
                if (label === optionLabel) { label = 'Opção'; }
            } else if (el.tagName === 'SELECT') {
                value = Array.prototype.filter.call(el.options, function (o) { return o.selected && o.value !== ''; })
                    .map(function (o) { return clean(o.textContent); }).join(', ');
                label = labelFor(form, el);
            } else {
                value = (el.value || '').trim();
                label = labelFor(form, el);
            }
            if (!value) { return; }
            var key = el.name || label;
            if (byName[key] !== undefined) {
                rows[byName[key]].value += ', ' + value;
                return;
            }
            byName[key] = rows.length;
            rows.push({ label: label || 'Campo', value: value });
        });
        return rows;
    }

    function message(form, rows) {
        var lines = [cfg.intro || 'Olá! Acabei de enviar este pedido pelo site:'];
        var name = form.getAttribute('data-form-name') || form.getAttribute('name') || '';
        if (name && !/^(new form|form)$/i.test(name)) { lines.push('(' + clean(name) + ')'); }
        lines.push('');
        rows.forEach(function (r) { lines.push('*' + r.label + ':* ' + r.value); });
        return lines.join('\n');
    }

    function go(form) {
        if (redirecting) { return; }
        var rows = captured.get(form);
        if (!rows || !rows.length) { return; }
        redirecting = true;
        var url = 'https://wa.me/' + cfg.number + '?text=' + encodeURIComponent(message(form, rows));
        window.setTimeout(function () { window.location.href = url; }, 900);
        window.setTimeout(function () { redirecting = false; }, 4000);
    }

    // Captura os valores no momento do envio (antes de o formulário ser limpo).
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM' || !wanted(form)) { return; }
        if (!form.matches('[data-element_type="e-form"], .elementor-form')) { return; }
        captured.set(form, collect(form));
        if (form.matches('[data-element_type="e-form"]') && !form.__fsWaObserver) {
            form.__fsWaObserver = new MutationObserver(function () {
                if (form.classList.contains('form-state-success')) { go(form); }
            });
            form.__fsWaObserver.observe(form, { attributes: true, attributeFilter: ['class'] });
        }
    }, true);

    // Formulário clássico do Elementor Pro: evento jQuery "submit_success".
    function bindClassic() {
        var $ = window.jQuery;
        if (!$) { return; }
        $(document).off('submit_success.fsWa').on('submit_success.fsWa', function (e) {
            var form = e.target && e.target.tagName === 'FORM' ? e.target : (e.target && e.target.closest ? e.target.closest('form') : null);
            if (form && captured.has(form)) { go(form); }
        });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', bindClassic); } else { bindClassic(); }
})();
