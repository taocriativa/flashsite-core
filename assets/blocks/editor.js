/* FlashSite Core · blocos no editor (JS simples, sem compilação). */
(function (wp) {
	'use strict';
	var el = wp.element.createElement;
	var useEffect = wp.element.useEffect;
	var be = wp.blockEditor;
	var c = wp.components;
	var SSR = wp.serverSideRender;

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
})(window.wp);
