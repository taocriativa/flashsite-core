/* FlashSite Core · blocos no editor (JS simples, sem compilação). */
(function (wp) {
	'use strict';
	var el = wp.element.createElement;
	var useEffect = wp.element.useEffect;
	var be = wp.blockEditor;
	var c = wp.components;
	var SSR = wp.serverSideRender;

	/* ── Ligações: o editor mostra os valores reais (só leitura) ── */
	var dados = window.flashsiteBindings || { business: {}, links: {}, items: {} };
	var FONTES = [
		{
			name: 'flashsite/business',
			label: 'Dados do Negócio',
			getValues: function (o) {
				var v = {};
				Object.keys(o.bindings).forEach(function (attr) {
					var a = o.bindings[attr].args || {};
					v[attr] = a.link ? (dados.links[a.link] || '') : (dados.business[a.field] || ('[' + (a.field || 'campo') + ']'));
				});
				return v;
			},
			canUserEditValue: function () { return false; }
		},
		{
			name: 'flashsite/item',
			label: 'Item da coleção',
			usesContext: ['postId', 'postType'],
			getValues: function (o) {
				var item = dados.items[(o.context || {}).postId] || null, v = {};
				Object.keys(o.bindings).forEach(function (attr) {
					var a = o.bindings[attr].args || {}, f = a.field || '';
					if (!item) { v[attr] = '[' + f + ']'; return; }
					var val = f.indexOf('terms:') === 0 ? '' : (item[f] || '');
					if (a.label) val = val ? a.label : '';
					v[attr] = val || '\u200b'; // vazio: no site o bloco some; no editor fica em branco
				});
				return v;
			},
			canUserEditValue: function () { return false; }
		}
	];
	// O WordPress regista primeiro a fonte vinda do servidor (só com o nome): substituímos pela versão com valores.
	function garanteFontes() {
		if (!wp.blocks.registerBlockBindingsSource || !wp.blocks.getBlockBindingsSource) return true;
		var pronto = true;
		FONTES.forEach(function (f) {
			var atual = wp.blocks.getBlockBindingsSource(f.name);
			if (atual && atual.getValues) return;
			if (atual && wp.blocks.unregisterBlockBindingsSource) wp.blocks.unregisterBlockBindingsSource(f.name);
			try { wp.blocks.registerBlockBindingsSource(f); } catch (e) { pronto = false; }
		});
		return pronto;
	}
	garanteFontes();
	if (wp.domReady) wp.domReady(garanteFontes);
	var tentativas = 0, parar = wp.data.subscribe(function () {
		tentativas++;
		var s = wp.blocks.getBlockBindingsSource && wp.blocks.getBlockBindingsSource('flashsite/item');
		if (s && !s.getValues) garanteFontes();
		if ((s && s.getValues) || tentativas > 500) { parar(); }
	});

	/* ── Formulário ───────────────────────────────────────── */
	wp.blocks.registerBlockType('flashsite/form', {
		edit: function (props) {
			var a = props.attributes, set = props.setAttributes;
			useEffect(function () {
				if (!a.formId) set({ formId: 'f' + Math.random().toString(36).slice(2, 8) });
			}, []);
			var blockProps = be.useBlockProps({ className: 'fs-form' });
			var inner = be.useInnerBlocksProps({ className: 'fs-form__campos' }, {
				allowedBlocks: ['flashsite/form-field'],
				template: [
					['flashsite/form-field', { label: 'Nome', name: 'nome', required: true }],
					['flashsite/form-field', { label: 'Telefone', name: 'telefone', type: 'tel', required: true }],
					['flashsite/form-field', { label: 'Mensagem', name: 'mensagem', type: 'textarea' }]
				]
			});
			return el(wp.element.Fragment, null,
				el(be.InspectorControls, null,
					el(c.PanelBody, { title: 'Envio' },
						el(c.TextControl, { label: 'Texto do botão', value: a.submitLabel, onChange: function (v) { set({ submitLabel: v }); } }),
						el(c.TextControl, { label: 'Assunto do e-mail', help: 'Vai para o destinatário dos Dados do Negócio.', value: a.subject, onChange: function (v) { set({ subject: v }); } }),
						el(c.TextareaControl, { label: 'Mensagem de sucesso', value: a.successMessage, onChange: function (v) { set({ successMessage: v }); } }),
						el(c.TextareaControl, { label: 'Mensagem de erro', value: a.errorMessage, onChange: function (v) { set({ errorMessage: v }); } }),
						el(c.ToggleControl, { label: 'Abrir o WhatsApp com o resumo', checked: !!a.whatsapp, onChange: function (v) { set({ whatsapp: v }); } }),
						a.whatsapp ? el(c.TextControl, { label: 'Primeira linha da mensagem', value: a.whatsappIntro, onChange: function (v) { set({ whatsappIntro: v }); } }) : null
					)
				),
				el('div', blockProps,
					el('div', inner),
					el('div', { className: 'wp-block-button fs-form__enviar' },
						el(be.RichText, { tagName: 'span', className: 'wp-block-button__link wp-element-button', value: a.submitLabel, allowedFormats: [], onChange: function (v) { set({ submitLabel: v }); } })
					)
				)
			);
		},
		save: function () { return el(be.InnerBlocks.Content); }
	});

	/* ── Campo ────────────────────────────────────────────── */
	var TYPES = [
		{ label: 'Texto', value: 'text' }, { label: 'E-mail', value: 'email' }, { label: 'Telefone', value: 'tel' },
		{ label: 'Data', value: 'date' }, { label: 'Hora', value: 'time' }, { label: 'Número', value: 'number' },
		{ label: 'Lista de opções', value: 'select' }, { label: 'Texto longo', value: 'textarea' }
	];
	wp.blocks.registerBlockType('flashsite/form-field', {
		edit: function (props) {
			var a = props.attributes, set = props.setAttributes;
			var blockProps = be.useBlockProps({ className: 'fs-campo fs-campo--' + a.type + (a.width === 'half' ? ' fs-campo--meio' : '') });
			var options = (a.options || '').split(/\n/).filter(Boolean);
			var control = a.type === 'textarea' ? el('textarea', { rows: 3, disabled: true, placeholder: a.placeholder })
				: a.type === 'select' ? el('select', { disabled: true }, options.map(function (o) { return el('option', { key: o }, o); }))
				: el('input', { type: a.type, disabled: true, placeholder: a.placeholder });
			return el(wp.element.Fragment, null,
				el(be.InspectorControls, null,
					el(c.PanelBody, { title: 'Campo' },
						el(c.TextControl, { label: 'Rótulo', value: a.label, onChange: function (v) { set({ label: v }); } }),
						el(c.SelectControl, { label: 'Tipo', value: a.type, options: TYPES, onChange: function (v) { set({ type: v }); } }),
						el(c.ToggleControl, { label: 'Obrigatório', checked: !!a.required, onChange: function (v) { set({ required: v }); } }),
						el(c.SelectControl, { label: 'Largura', value: a.width, options: [{ label: 'Linha inteira', value: 'full' }, { label: 'Meia linha', value: 'half' }], onChange: function (v) { set({ width: v }); } }),
						a.type === 'select'
							? el(c.TextareaControl, { label: 'Opções (uma por linha)', value: a.options, onChange: function (v) { set({ options: v }); } })
							: el(c.TextControl, { label: 'Texto de exemplo', value: a.placeholder, onChange: function (v) { set({ placeholder: v }); } }),
						el(c.TextControl, { label: 'Valor inicial', value: a.defaultValue, onChange: function (v) { set({ defaultValue: v }); } }),
						el(c.TextControl, { label: 'Nome técnico', help: 'Usado no e-mail e no WhatsApp. Vazio = gerado do rótulo.', value: a.name, onChange: function (v) { set({ name: v }); } })
					)
				),
				el('div', blockProps,
					el(be.RichText, { tagName: 'label', className: 'fs-campo__rotulo', value: a.label, allowedFormats: [], onChange: function (v) { set({ label: v }); } }),
					control
				)
			);
		},
		save: function () { return null; }
	});

	/* ── Horário e mapa: pré-visualização do servidor ─────── */
	wp.blocks.registerBlockType('flashsite/hours', {
		edit: function (props) {
			var a = props.attributes, set = props.setAttributes;
			return el(wp.element.Fragment, null,
				el(be.InspectorControls, null,
					el(c.PanelBody, { title: 'Horário' },
						el(c.SelectControl, { label: 'Dias', value: a.format, options: [{ label: 'Curtos (Seg, Ter a Sáb)', value: 'curto' }, { label: 'Por extenso', value: 'longo' }], onChange: function (v) { set({ format: v }); } }),
						el('p', null, 'O horário muda em FlashSite › Dados do Negócio.')
					)
				),
				el('div', be.useBlockProps(), el(SSR, { block: 'flashsite/hours', attributes: a }))
			);
		},
		save: function () { return null; }
	});
	wp.blocks.registerBlockType('flashsite/map', {
		edit: function (props) {
			var a = props.attributes, set = props.setAttributes;
			return el(wp.element.Fragment, null,
				el(be.InspectorControls, null,
					el(c.PanelBody, { title: 'Mapa' },
						el(c.RangeControl, { label: 'Altura (px)', min: 200, max: 900, value: a.height, onChange: function (v) { set({ height: v }); } })
					)
				),
				el('div', be.useBlockProps(), el(SSR, { block: 'flashsite/map', attributes: a, EmptyResponsePlaceholder: function () { return el('p', null, 'Sem mapa nos Dados do Negócio.'); } }))
			);
		},
		save: function () { return null; }
	});
	wp.blocks.registerBlockType('flashsite/item-fields', {
		edit: function (props) {
			return el('div', be.useBlockProps(), el(SSR, { block: 'flashsite/item-fields', attributes: props.attributes, urlQueryArgs: { post_id: props.context && props.context.postId }, EmptyResponsePlaceholder: function () { return el('p', null, 'Ficha do item: aparece na página de cada item da coleção.'); } }));
		},
		save: function () { return null; }
	});
	/* Blocos de restaurante: pré-visualização do servidor + definições simples. */
	function ssrBlock(name, panel) {
		wp.blocks.registerBlockType(name, {
			edit: function (props) {
				return el(wp.element.Fragment, null,
					panel ? el(be.InspectorControls, null, panel(props)) : null,
					el('div', be.useBlockProps(), el(SSR, { block: name, attributes: props.attributes, EmptyResponsePlaceholder: function () { return el('p', null, wp.blocks.getBlockType(name).title + ': sem dados para mostrar (confira os Dados do Negócio).'); } }))
				);
			},
			save: function () { return null; }
		});
	}
	ssrBlock('flashsite/open-now', function () { return el(c.PanelBody, { title: 'Aberto agora' }, el('p', null, 'Usa o horário dos Dados do Negócio. No editor aparece o estado no servidor; no site é calculado na hora de quem visita.')); });
	ssrBlock('flashsite/menu-filter', function (props) { return el(c.PanelBody, { title: 'Filtro' }, el(c.TextareaControl, { label: 'Aviso', value: props.attributes.aviso, onChange: function (v) { props.setAttributes({ aviso: v }); } })); });
	ssrBlock('flashsite/legal', function (props) {
		var a = props.attributes, set = props.setAttributes;
		return el(c.PanelBody, { title: 'Informação legal' },
			el(c.TextControl, { label: 'Couvert (Portugal)', help: 'Ex.: "Couvert: pão, manteiga e azeitonas, 2,50 €. Só servido se pedir."', value: a.couvert, onChange: function (v) { set({ couvert: v }); } }),
			el(c.ToggleControl, { label: 'Mostrar Livro de Reclamações (Portugal)', checked: !!a.livro, onChange: function (v) { set({ livro: v }); } }),
			el(c.TextareaControl, { label: 'Texto legal (Brasil)', help: 'Só se a lei do seu estado ou município exigir.', value: a.textoBR, onChange: function (v) { set({ textoBR: v }); } })
		);
	});
})(window.wp);
