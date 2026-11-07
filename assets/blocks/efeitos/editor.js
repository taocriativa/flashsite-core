/* FlashSite Core · painel "Efeito de movimento" em todos os blocos core/ e flashsite/. */
(function (wp) {
	'use strict';
	var el = wp.element.createElement;
	var hooks = wp.hooks, be = wp.blockEditor, c = wp.components;
	var OPCOES = [
		{ label: 'Nenhum', value: '' },
		{ label: 'Entrada suave (sobe e aparece)', value: 'entrada' },
		{ label: 'Revelar imagem', value: 'revelar' },
		{ label: 'Parallax suave (imagem)', value: 'parallax' },
		{ label: 'Balanço subtil (selos e autocolantes)', value: 'balanco' }
	];
	function aplica(name) { return name.indexOf('core/') === 0 || name.indexOf('flashsite/') === 0; }

	hooks.addFilter('blocks.registerBlockType', 'flashsite/efeitos/atributos', function (settings, name) {
		if (!aplica(name)) return settings;
		settings.attributes = Object.assign({}, settings.attributes, {
			fsEffect: { type: 'string', default: '' },
			fsDelay: { type: 'number', default: 0 }
		});
		return settings;
	});

	hooks.addFilter('editor.BlockEdit', 'flashsite/efeitos/painel', wp.compose.createHigherOrderComponent(function (BlockEdit) {
		return function (props) {
			if (!aplica(props.name)) return el(BlockEdit, props);
			var a = props.attributes, set = props.setAttributes;
			return el(wp.element.Fragment, null,
				el(BlockEdit, props),
				el(be.InspectorControls, null,
					el(c.PanelBody, { title: 'Efeito de movimento', initialOpen: !!a.fsEffect },
						el(c.SelectControl, { label: 'Efeito', value: a.fsEffect || '', options: OPCOES, onChange: function (v) { set({ fsEffect: v }); } }),
						a.fsEffect && a.fsEffect !== 'parallax'
							? el(c.RangeControl, { label: 'Atraso (ms)', help: 'Útil para entrar em sequência (0, 100, 200…).', min: 0, max: 1200, step: 50, value: a.fsDelay || 0, onChange: function (v) { set({ fsDelay: v || 0 }); } })
							: null,
						el('p', { style: { fontSize: '12px', color: '#757575' } }, 'O efeito aparece no site publicado. Quem pede "reduzir movimento" no aparelho vê o bloco parado.')
					)
				)
			);
		};
	}, 'flashsiteEfeitos'));
})(window.wp);
