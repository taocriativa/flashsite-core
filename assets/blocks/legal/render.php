<?php
/** @var array $attributes */
defined('ABSPATH') || exit;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Modules\Blocks\BlocksModule;

$registry = BlocksModule::service(CollectionRegistry::class);
$market = $registry instanceof CollectionRegistry ? $registry->market() : 'PT';
$items = [];
if ($market === 'BR') {
	$texto = trim((string) ($attributes['textoBR'] ?? ''));
	if ($texto !== '') {
		$items[] = esc_html($texto);
	}
} else {
	$items[] = 'Preços com IVA incluído à taxa legal em vigor.';
	$couvert = trim((string) ($attributes['couvert'] ?? ''));
	if ($couvert !== '') {
		$items[] = esc_html($couvert);
	}
	if (! empty($attributes['livro'])) {
		$icone = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M3 5.5c3-1.3 6-1.3 9 .7v13c-3-2-6-2-9-.7z"/><path d="M21 5.5c-3-1.3-6-1.3-9 .7v13c3-2 6-2 9-.7z"/></svg>';
		$items[] = '<a href="https://www.livroreclamacoes.pt/Inicio/" target="_blank" rel="noopener">' . $icone . 'Livro de Reclamações</a>';
	}
}
if ($items === []) {
	return;
}
wp_enqueue_style('flashsite-blocks');
?>
<p <?php echo get_block_wrapper_attributes(['class' => 'fs-legal']); ?>><?php echo implode('<span aria-hidden="true">·</span>', array_map(static fn ($i) => '<span>' . $i . '</span>', $items)); // phpcs:ignore -- itens escapados acima ?></p>
