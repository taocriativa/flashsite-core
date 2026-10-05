<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Output\Elementor;

use FlashSite\Core\Domain\Collections\FieldFormatter;

require_once __DIR__ . '/CollectionTagBase.php';

/**
 * Texto formatado de qualquer campo público do item atual.
 * Ex.: área "92 m²", classe energética "B", preço "285.000,00 €".
 *
 * Settings: field (chave do campo), money (cents|auto), before, after, fallback.
 *
 * @since 2.6.0
 */
class ItemFieldTag extends CollectionTagBase
{
    protected function slug(): string { return 'flashsite-item-field'; }
    protected function title(): string { return 'Item · Campo'; }

    /** Campo fixo nas subclasses (tags de atalho). */
    protected function fixedField(): ?string { return null; }

    public function get_categories(): array
    {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    protected function register_controls(): void
    {
        $this->registerItemSourceControls();
        if ($this->fixedField() === null) {
            $this->add_control('field', [
                'label' => 'Campo',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => self::fieldOptions(),
                'default' => '',
            ]);
        }
        $this->add_control('money', [
            'label' => 'Valores em €',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                FieldFormatter::MONEY_CENTS => 'Com cêntimos (285.000,00 €)',
                FieldFormatter::MONEY_AUTO => 'Sem ,00 (285.000 €)',
            ],
            'default' => FieldFormatter::MONEY_CENTS,
        ]);
        $this->add_control('before', ['label' => 'Antes', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('after', ['label' => 'Depois', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('fallback', ['label' => 'Se estiver vazio', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
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
        $postId = $this->currentPostId();
        $preset = $this->currentPreset($postId);
        $formatter = self::formatter();
        $value = '';

        if ($preset !== null && $formatter !== null) {
            $value = $this->resolve($preset, $postId, $formatter);
        }

        if ($value === '') {
            return (string) ($this->get_settings('fallback') ?? '');
        }
        return (string) ($this->get_settings('before') ?? '') . $value . (string) ($this->get_settings('after') ?? '');
    }

    protected function resolve(\FlashSite\Core\Domain\Collections\CollectionPresetInterface $preset, int $postId, FieldFormatter $formatter): string
    {
        $key = $this->fixedField() ?? (string) ($this->get_settings('field') ?? '');
        $field = $preset->field($key);
        if ($field === null) {
            return '';
        }
        $style = (string) ($this->get_settings('money') ?: FieldFormatter::MONEY_CENTS);
        return $formatter->field($preset, $field, $postId, $style);
    }
}
