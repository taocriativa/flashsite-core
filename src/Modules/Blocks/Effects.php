<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Blocks;

/**
 * Efeitos de movimento nos blocos (@since 3.6.0-blocks.2).
 *
 * Painel "Efeito de movimento" no editor (atributos fsEffect e fsDelay). Nada muda no HTML gravado:
 * a classe entra só na renderização, por isso desligar o efeito nunca invalida um bloco.
 * Regras: só transform e opacity; "reduzir movimento" desliga tudo; sem JavaScript o conteúdo aparece.
 */
final class Effects
{
    public const EFFECTS = ['entrada', 'revelar', 'parallax', 'balanco'];

    private bool $used = false;

    public function register(): void
    {
        add_filter('register_block_type_args', [$this, 'addAttributes'], 10, 2);
        add_filter('render_block', [$this, 'applyClass'], 20, 2);
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
        add_action('wp_footer', [$this, 'enqueueIfUsed'], 1);
    }

    /** @param array<string, mixed> $args */
    public function addAttributes(array $args, string $name): array
    {
        if (! str_starts_with($name, 'core/') && ! str_starts_with($name, 'flashsite/')) {
            return $args;
        }
        $args['attributes'] = (array) ($args['attributes'] ?? []);
        $args['attributes']['fsEffect'] = ['type' => 'string', 'default' => ''];
        $args['attributes']['fsDelay'] = ['type' => 'number', 'default' => 0];
        return $args;
    }

    /** @param array<string, mixed> $block */
    public function applyClass(string $content, array $block): string
    {
        $effect = (string) ($block['attrs']['fsEffect'] ?? '');
        if ($effect === '' || ! in_array($effect, self::EFFECTS, true) || trim($content) === '') {
            return $content;
        }
        $tags = new \WP_HTML_Tag_Processor($content);
        if (! $tags->next_tag()) {
            return $content;
        }
        $tags->add_class('fs-fx');
        $tags->add_class('fs-fx--' . $effect);
        $delay = max(0, min(1200, (int) ($block['attrs']['fsDelay'] ?? 0)));
        if ($delay > 0) {
            $style = (string) $tags->get_attribute('style');
            $tags->set_attribute('style', trim($style . ';--fs-fx-delay:' . $delay . 'ms', ';'));
        }
        $this->used = true;
        return $tags->get_updated_html();
    }

    public function registerAssets(): void
    {
        wp_register_style('flashsite-efeitos', FLASHSITE_CORE_URL . 'assets/blocks/efeitos/efeitos.css', [], FLASHSITE_CORE_VERSION);
        wp_register_script('flashsite-efeitos', FLASHSITE_CORE_URL . 'assets/blocks/efeitos/efeitos.js', [], FLASHSITE_CORE_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
        // Em temas de blocos a página já foi renderizada antes do <head>: se houver efeito, o CSS entra no topo.
        if ($this->used) {
            wp_enqueue_style('flashsite-efeitos');
        }
    }

    public function enqueueIfUsed(): void
    {
        if ($this->used) {
            wp_enqueue_style('flashsite-efeitos');
            wp_enqueue_script('flashsite-efeitos');
        }
    }
}
