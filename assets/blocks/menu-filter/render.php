<?php
/** @var array $attributes */
defined('ABSPATH') || exit;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Modules\Blocks\BlocksModule;

$registry = BlocksModule::service(CollectionRegistry::class);
$preset = $registry instanceof CollectionRegistry ? $registry->get('prato') : null;
$field = $preset !== null ? $preset->field('alergenios') : null;
$allergens = $field !== null ? (array) $field->options : [];
wp_enqueue_style('flashsite-blocks');
wp_enqueue_script('flashsite-menu-filter');
$id = wp_unique_id('fs-filtro-');
?>
<div <?php echo get_block_wrapper_attributes(['class' => 'fs-filtro']); ?> data-fs-filtro>
	<div class="fs-filtro__linha" role="group" aria-label="Filtrar a carta">
		<button type="button" class="fs-filtro__chip" aria-pressed="false" data-dieta="vegetariano">Vegetariano</button>
		<button type="button" class="fs-filtro__chip" aria-pressed="false" data-dieta="vegano">Vegano</button>
		<button type="button" class="fs-filtro__chip" aria-pressed="false" data-dieta="sem_gluten">Sem glúten</button>
		<?php if ($allergens !== []) : ?>
			<label class="fs-filtro__sem" for="<?php echo esc_attr($id); ?>"><span class="screen-reader-text">Sem o alergénio</span>
				<select id="<?php echo esc_attr($id); ?>" data-sem>
					<option value="">Sem… (alergénio)</option>
					<?php foreach ($allergens as $key => $label) : ?>
						<option value="<?php echo esc_attr((string) $key); ?>">Sem <?php echo esc_html(mb_strtolower((string) $label)); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php endif; ?>
	</div>
	<p class="fs-filtro__estado" aria-live="polite"></p>
	<p class="fs-filtro__aviso"><?php echo esc_html((string) ($attributes['aviso'] ?? '')); ?></p>
</div>
