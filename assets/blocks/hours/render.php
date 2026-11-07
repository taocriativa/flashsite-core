<?php
/** @var array $attributes */
defined('ABSPATH') || exit;
wp_enqueue_style('flashsite-blocks'); // só nas páginas que usam blocos Flash Site
use FlashSite\Core\Domain\Business\BusinessData;
use FlashSite\Core\Modules\Blocks\BlocksModule;

$business = BlocksModule::service(BusinessData::class);
if (! $business instanceof BusinessData) {
	return;
}
$long = ($attributes['format'] ?? 'curto') === 'longo';
$names = $long
	? ['monday' => 'Segunda', 'tuesday' => 'Terça', 'wednesday' => 'Quarta', 'thursday' => 'Quinta', 'friday' => 'Sexta', 'saturday' => 'Sábado', 'sunday' => 'Domingo']
	: ['monday' => 'Seg', 'tuesday' => 'Ter', 'wednesday' => 'Qua', 'thursday' => 'Qui', 'friday' => 'Sex', 'saturday' => 'Sáb', 'sunday' => 'Dom'];
$groups = [];
foreach ($names as $day => $label) {
	$value = trim((string) $business->get('hours.' . $day, ''));
	if ($value === '') {
		continue;
	}
	$last = count($groups) - 1;
	if ($last >= 0 && $groups[$last]['value'] === $value && $groups[$last]['next'] === $day) {
		$groups[$last]['to'] = $label;
	} else {
		$groups[] = ['from' => $label, 'to' => '', 'value' => $value];
		$last = count($groups) - 1;
	}
	$keys = array_keys($names);
	$i = array_search($day, $keys, true);
	$groups[$last]['next'] = $keys[$i + 1] ?? '';
}
if ($groups === []) {
	return;
}
$notes = trim((string) $business->get('hours.notes', ''));
?>
<dl <?php echo get_block_wrapper_attributes(['class' => 'fs-horario']); ?>>
	<?php foreach ($groups as $g) : ?>
		<div class="fs-horario__linha"><dt><?php echo esc_html($g['to'] !== '' ? $g['from'] . ' a ' . $g['to'] : $g['from']); ?></dt><dd><?php echo esc_html($g['value']); ?></dd></div>
	<?php endforeach; ?>
	<?php if ($notes !== '') : ?><div class="fs-horario__nota"><dd><?php echo esc_html($notes); ?></dd></div><?php endif; ?>
</dl>
