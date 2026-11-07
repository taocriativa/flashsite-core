<?php
/** @var array $attributes */
defined('ABSPATH') || exit;
use FlashSite\Core\Modules\Blocks\OpeningHours;

$week = OpeningHours::fromBusinessData();
if ($week === null) {
	return; // horário em texto que não dá para ler com segurança: melhor não mostrar nada do que mostrar errado
}
wp_enqueue_style('flashsite-blocks');
wp_enqueue_script('flashsite-open-now');
?>
<p <?php echo get_block_wrapper_attributes(['class' => 'fs-aberto']); ?> data-horario="<?php echo esc_attr((string) wp_json_encode($week)); ?>" data-fuso="<?php echo esc_attr(wp_timezone_string()); ?>" hidden><span class="fs-aberto__ponto" aria-hidden="true"></span><span class="fs-aberto__texto"></span></p>
