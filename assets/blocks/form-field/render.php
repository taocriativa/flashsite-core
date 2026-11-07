<?php
/** @var array $attributes */
defined('ABSPATH') || exit;
use FlashSite\Core\Modules\Blocks\FormHandler;

$type = sanitize_key((string) ($attributes['type'] ?? 'text'));
$name = FormHandler::fieldName($attributes);
$label = (string) ($attributes['label'] ?? '');
$required = ! empty($attributes['required']);
$placeholder = (string) ($attributes['placeholder'] ?? '');
$default = (string) ($attributes['defaultValue'] ?? '');
$id = 'fs-' . sanitize_key((string) ($block->context['flashsite/formId'] ?? 'f')) . '-' . $name;
$autocomplete = ['nome' => 'name', 'name' => 'name', 'email' => 'email', 'telefone' => 'tel', 'phone' => 'tel'][$name] ?? ($type === 'tel' ? 'tel' : ($type === 'email' ? 'email' : 'off'));
$common = sprintf(' id="%s" name="%s"%s', esc_attr($id), esc_attr($name), $required ? ' required aria-required="true"' : '');
$width = ($attributes['width'] ?? 'full') === 'half' ? ' fs-campo--meio' : '';
?>
<div class="fs-campo fs-campo--<?php echo esc_attr($type . $width); ?>">
	<label class="fs-campo__rotulo" for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
	<?php if ($type === 'textarea') : ?>
		<textarea<?php echo $common; ?> rows="4" placeholder="<?php echo esc_attr($placeholder); ?>"><?php echo esc_textarea($default); ?></textarea>
	<?php elseif ($type === 'select') : ?>
		<select<?php echo $common; ?>>
			<?php foreach (FormHandler::options((string) ($attributes['options'] ?? '')) as $option) : ?>
				<option value="<?php echo esc_attr($option); ?>"<?php selected($option, $default); ?>><?php echo esc_html($option); ?></option>
			<?php endforeach; ?>
		</select>
	<?php else : ?>
		<input type="<?php echo esc_attr($type); ?>"<?php echo $common; ?> autocomplete="<?php echo esc_attr($autocomplete); ?>" placeholder="<?php echo esc_attr($placeholder); ?>" value="<?php echo esc_attr($default); ?>"<?php echo $type === 'tel' ? ' inputmode="tel"' : ''; ?>>
	<?php endif; ?>
	<span class="fs-campo__erro" aria-live="polite"></span>
</div>
