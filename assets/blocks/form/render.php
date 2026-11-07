<?php
/** @var array $attributes @var string $content @var WP_Block $block */
defined('ABSPATH') || exit;
$form_id = sanitize_key((string) ($attributes['formId'] ?? ''));
$post_id = (int) get_the_ID();
$wrapper = get_block_wrapper_attributes(['class' => 'fs-form']);
?>
<form <?php echo $wrapper; ?> method="post" novalidate
	data-endpoint="<?php echo esc_url(rest_url('flashsite/v1/form')); ?>"
	data-post="<?php echo esc_attr((string) $post_id); ?>"
	data-form="<?php echo esc_attr($form_id); ?>"
	data-success="<?php echo esc_attr((string) ($attributes['successMessage'] ?? '')); ?>"
	data-error="<?php echo esc_attr((string) ($attributes['errorMessage'] ?? '')); ?>">
	<div class="fs-form__campos"><?php echo $content; // phpcs:ignore -- campos renderizados pelos blocos filhos ?></div>
	<p class="fs-form__isco" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
	<div class="wp-block-button fs-form__enviar"><button type="submit" class="wp-block-button__link wp-element-button"><?php echo esc_html((string) ($attributes['submitLabel'] ?? 'Enviar')); ?></button></div>
	<p class="fs-form__estado" role="status" aria-live="polite"></p>
</form>
