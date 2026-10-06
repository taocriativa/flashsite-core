<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class HoursFormattedTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-hours-formatted'; }
    protected function getTagTitle(): string { return 'FlashSite: Horários (Texto Completo)'; }
    protected function getTagPath(): string { return '_flashsite.format.hours_formatted'; }

    /** @since 2.6.0 "Uma linha por dia": cada grupo de dias numa linha, com os dias em negrito. */
    protected function register_controls(): void
    {
        if (! class_exists('\\Elementor\\Controls_Manager')) {
            return;
        }
        $this->add_control('hours_layout', [
            'label' => 'Apresentação',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => ['inline' => 'Texto corrido (Seg a Sex: … · Sáb: …)', 'lines' => 'Uma linha por dia, dias em negrito'],
            'default' => 'inline',
        ]);
    }

    /**
     * Com Data_Tag (Elementor atual) o conteúdo vem de get_value(); com Tag vem de render().
     * Os dois caminhos passam por aqui.
     */
    public function get_value(array $options = []): string
    {
        $value = parent::get_value($options);
        if ((string) $this->get_settings('hours_layout') !== 'lines') {
            return $value;
        }
        $rows = [];
        foreach (explode(' · ', $value) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode(': ', $line, 2);
            $rows[] = count($parts) === 2
                ? '<strong>' . esc_html($parts[0]) . '</strong> ' . esc_html($parts[1])
                : esc_html($line);
        }
        return implode('<br>', $rows);
    }

    public function render(): void
    {
        if ((string) $this->get_settings('hours_layout') !== 'lines') {
            parent::render();
            return;
        }
        echo $this->get_value(); // phpcs:ignore WordPress.Security.EscapeOutput -- partes escapadas em get_value()
    }
}
