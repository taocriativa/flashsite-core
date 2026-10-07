<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Output\Elementor;

require_once __DIR__ . '/CollectionTagBase.php';

/**
 * Nome de um campo ou de uma lista do item, no termo do país do site
 * (ex.: "Tipologia" em Portugal, "Quartos" no Brasil; "Casas de banho" / "Banheiros").
 * Usar nos rótulos das fichas em vez de texto fixo.
 *
 * Settings: target ("field:<chave>" ou "tax:<chave>"), before, after.
 *
 * @since 3.0.0
 */
class ItemLabelTag extends CollectionTagBase
{
    protected function slug(): string { return 'flashsite-item-label'; }
    protected function title(): string { return 'Item · Nome do campo'; }

    public function get_categories(): array
    {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    protected function register_controls(): void
    {
        $this->registerItemSourceControls();
        $options = [];
        foreach (self::fieldOptions() as $key => $label) {
            $options['field:' . $key] = $label;
        }
        foreach (self::taxonomyOptions() as $key => $label) {
            $options['tax:' . $key] = $label;
        }
        $this->add_control('target', [
            'label' => 'Campo ou lista',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => $options,
            'default' => '',
        ]);
        $this->add_control('before', ['label' => 'Antes', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('after', ['label' => 'Depois', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
    }

    public function get_value(array $options = []): string
    {
        return $this->text();
    }

    public function render(): void
    {
        echo esc_html($this->text());
    }

    protected function text(): string
    {
        $preset = $this->currentPreset($this->currentPostId());
        $target = (string) ($this->get_settings('target') ?? '');
        if ($preset === null || ! str_contains($target, ':')) {
            return '';
        }
        [$kind, $key] = explode(':', $target, 2);
        $label = '';
        if ($kind === 'field') {
            $label = $preset->field($key)?->label ?? '';
        } elseif ($kind === 'tax') {
            $label = $preset->taxonomy($key)?->singularLabel ?? '';
        }
        if ($label === '') {
            return '';
        }
        return (string) ($this->get_settings('before') ?? '') . $label . (string) ($this->get_settings('after') ?? '');
    }
}
