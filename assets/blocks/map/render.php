<?php
/** @var array $attributes */
defined('ABSPATH') || exit;
wp_enqueue_style('flashsite-blocks'); // só nas páginas que usam blocos Flash Site
$map = do_shortcode('[flashsite_maps]');
if (trim($map) === '') {
	return;
}
$height = max(200, min(900, (int) ($attributes['height'] ?? 360)));
?>
<div <?php echo get_block_wrapper_attributes(['class' => 'fs-mapa', 'style' => '--fs-mapa-altura:' . $height . 'px']); ?>><?php echo $map; // phpcs:ignore -- iframe do Google validado pelo Core ?></div>
