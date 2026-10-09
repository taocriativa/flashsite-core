<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\SiteTexts;

use FlashSite\Core\Core\Plugin;
use FlashSite\Core\Domain\SiteTexts\SiteTextsRegistry;

require_once dirname(__DIR__) . '/OutputFoundation/Elementor/ElementorTagBase.php';

/**
 * Tag "Texto do site" (@since 3.1.0): marca um texto da página como editável pelo cliente em
 * FlashSite › Textos do site. Mostra o valor do cliente ou, sem ele, o texto padrão.
 */
class SiteTextTag extends \FlashSite\Core\Modules\OutputFoundation\Elementor\ElementorTagBase
{
    public function get_name(): string { return SiteTextsRegistry::TAG; }
    public function get_title(): string { return 'Texto do site (editável pelo cliente)'; }
    public function get_group(): string { return 'flashsite'; }

    public function get_categories(): array
    {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    protected function register_controls(): void
    {
        $this->add_control('key', [
            'label' => 'Chave (única, ex.: inicio_titulo)',
            'type' => \Elementor\Controls_Manager::TEXT,
        ]);
        $this->add_control('label', [
            'label' => 'Nome do campo para o cliente',
            'type' => \Elementor\Controls_Manager::TEXT,
        ]);
        $this->add_control('section', [
            'label' => 'Parte da página (ex.: Topo, Sobre)',
            'type' => \Elementor\Controls_Manager::TEXT,
        ]);
        $this->add_control('type', [
            'label' => 'Tipo',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => ['line' => 'Uma linha', 'paragraph' => 'Parágrafo'],
            'default' => 'line',
        ]);
        $this->add_control('max', [
            'label' => 'Tamanho máximo (caracteres; vazio = calcula pelo texto padrão)',
            'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 0,
        ]);
        $this->add_control('default', [
            'label' => 'Texto padrão',
            'type' => \Elementor\Controls_Manager::TEXTAREA,
        ]);
    }

    public function get_value(array $options = []): string
    {
        $default = (string) ($this->get_settings('default') ?? '');
        $key = (string) ($this->get_settings('key') ?? '');
        $application = Plugin::instance()->application();
        if ($key === '' || $application === null || ! $application->container()->has(SiteTextsRegistry::class)) {
            return $default;
        }
        /** @var SiteTextsRegistry $registry */
        $registry = $application->container()->make(SiteTextsRegistry::class);
        return $registry->text($key, $default);
    }

    public function render(): void
    {
        $text = esc_html($this->get_value());
        echo $this->get_settings('type') === 'paragraph' ? nl2br($text, false) : $text; // phpcs:ignore WordPress.Security.EscapeOutput
    }
}
