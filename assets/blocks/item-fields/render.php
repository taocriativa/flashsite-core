<?php
/** @var array $attributes @var WP_Block $block */
defined('ABSPATH') || exit;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\FieldType;
use FlashSite\Core\Modules\Blocks\BlocksModule;

$post_id = (int) ($block->context['postId'] ?? get_the_ID());
$registry = BlocksModule::service(CollectionRegistry::class);
$formatter = BlocksModule::service(FieldFormatter::class);
if ($post_id <= 0 || ! $registry instanceof CollectionRegistry || ! $formatter instanceof FieldFormatter) {
	return;
}
$preset = $registry->byPostType((string) get_post_type($post_id));
if ($preset === null) {
	return;
}
$exclude = array_filter(array_map('trim', explode(',', (string) ($attributes['exclude'] ?? ''))));
$rows = [];
$tags = [];
foreach ($preset->publicFields() as $field) {
	if (in_array($field->key, $exclude, true) || in_array($field->type, [FieldType::Image, FieldType::Gallery, FieldType::File, FieldType::Geo], true)) {
		continue;
	}
	$value = $formatter->field($preset, $field, $post_id);
	if ($value === '') {
		continue;
	}
	if ($field->type === FieldType::Bool) {
		$tags[] = $field->label;
		continue;
	}
	$rows[] = [$field->label, $value];
}
if ($rows === [] && $tags === []) {
	return;
}
wp_enqueue_style('flashsite-blocks');
?>
<div <?php echo get_block_wrapper_attributes(['class' => 'fs-ficha']); ?>>
	<?php if ($tags !== []) : ?>
		<ul class="fs-ficha__selos"><?php foreach ($tags as $t) : ?><li><?php echo esc_html($t); ?></li><?php endforeach; ?></ul>
	<?php endif; ?>
	<?php if ($rows !== []) : ?>
		<dl class="fs-ficha__lista"><?php foreach ($rows as [$label, $value]) : ?><div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div><?php endforeach; ?></dl>
	<?php endif; ?>
</div>
