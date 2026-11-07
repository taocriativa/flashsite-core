<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Blocks;

use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Core\Plugin;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\FieldType;

/**
 * FlashSite › Blocos (@since 3.6.0-blocks.1): a ponte do Core com o editor de blocos do WordPress,
 * para sites feitos sem Elementor (tema FlashSite PRO).
 *
 * - Ligações de dados: `flashsite/business` (Dados do Negócio) e `flashsite/item` (campos de um item
 *   de coleção, dentro de um Query Loop). Ligam título, parágrafo, botão e imagem nativos.
 * - Blocos: formulário (`flashsite/form` + `flashsite/form-field`), horário (`flashsite/hours`) e mapa (`flashsite/map`).
 * - Um bloco ligado a um dado vazio não aparece (ex.: selo "Vegetariano" num prato que não é).
 */
final class BlocksModule implements ModuleInterface
{
    public const REST_NS = 'flashsite/v1';

    public function register(): void
    {
        add_action('init', [$this, 'registerBindings'], 5);
        add_action('init', [$this, 'registerBlocks']);
        add_action('rest_api_init', [$this, 'registerRoutes']);
        add_filter('render_block', [$this, 'dropEmptyBoundBlocks'], 10, 2);
        add_filter('block_categories_all', [$this, 'addCategory']);
        add_filter('query_loop_block_query_vars', [$this, 'featuredOnly'], 10, 2);
        add_action('enqueue_block_editor_assets', [$this, 'editorAssets']);
        (new Effects())->register();
    }

    public function editorAssets(): void
    {
        wp_enqueue_script('flashsite-efeitos-editor', FLASHSITE_CORE_URL . 'assets/blocks/efeitos/editor.js', ['wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components'], FLASHSITE_CORE_VERSION, true);
    }

    /**
     * Query Loop com `"fsDestaques": true` no atributo query: só itens marcados como destaque na
     * coleção (ex.: "Sugestão do chef"). O campo vem do preset (`featured_field`, por omissão "destaque").
     *
     * @param array<string, mixed> $query
     */
    public function featuredOnly(array $query, $block): array
    {
        $context = is_object($block) && isset($block->context['query']) ? (array) $block->context['query'] : [];
        if (empty($context['fsDestaques'])) {
            return $query;
        }
        $registry = self::service(CollectionRegistry::class);
        $preset = $registry instanceof CollectionRegistry ? $registry->byPostType((string) ($query['post_type'] ?? '')) : null;
        if ($preset === null) {
            return $query;
        }
        $featured = (string) ($preset->setting('featured_field', 'destaque') ?: 'destaque');
        $query['meta_query'] = array_merge((array) ($query['meta_query'] ?? []), [['key' => 'fs_' . $featured, 'value' => '1']]);
        return $query;
    }

    public function boot(): void {}
    public function isActive(): bool { return function_exists('register_block_bindings_source'); }
    public function getSlug(): string { return 'blocks'; }

    /** @param array<int, array<string, mixed>> $categories */
    public function addCategory(array $categories): array
    {
        array_unshift($categories, ['slug' => 'flashsite', 'title' => 'Flash Site', 'icon' => null]);
        return $categories;
    }

    public function registerBindings(): void
    {
        register_block_bindings_source('flashsite/business', [
            'label' => 'Dados do Negócio',
            'get_value_callback' => [$this, 'businessValue'],
        ]);
        register_block_bindings_source('flashsite/item', [
            'label' => 'Item da coleção',
            'get_value_callback' => [$this, 'itemValue'],
            'uses_context' => ['postId', 'postType'],
        ]);
    }

    /**
     * args: field = caminho dos Dados do Negócio ("contact.phone") ou campo universal;
     *       link  = phone | whatsapp | email | maps (devolve o endereço do link, para o URL de um botão).
     *
     * @param array<string, mixed> $args
     */
    public function businessValue(array $args): ?string
    {
        $links = [
            'phone' => '[flashsite_phone_link]',
            'whatsapp' => '[flashsite_whatsapp_link]',
            'email' => '[flashsite_email_link]',
            'maps' => '[flashsite_google_maps_url]',
        ];
        $link = sanitize_key((string) ($args['link'] ?? ''));
        if ($link !== '' && isset($links[$link])) {
            return trim(wp_strip_all_tags(do_shortcode($links[$link])));
        }
        $field = preg_replace('/[^a-z0-9_.]/i', '', (string) ($args['field'] ?? '')) ?? '';
        if ($field === '') {
            return null;
        }
        // Valores já formatados pelo Core (ex.: "full_address", "hours") usam o atalho próprio.
        if (! str_contains($field, '.') && shortcode_exists('flashsite_' . $field)) {
            return trim(do_shortcode('[flashsite_' . $field . ']'));
        }
        return trim(do_shortcode('[flashsite field="' . $field . '"]'));
    }

    /**
     * args: field = chave do campo no preset ("resumo", "porcao", "vegetariano"),
     *       ou "price" (preço formatado), "terms:<taxonomia>" (termos), "title";
     *       label = texto a mostrar quando um campo sim/não está ligado (ex.: "Vegetariano").
     *
     * @param array<string, mixed> $args
     */
    public function itemValue(array $args, $block = null): ?string
    {
        $postId = 0;
        if (is_object($block) && isset($block->context['postId'])) {
            $postId = (int) $block->context['postId'];
        }
        if ($postId <= 0) {
            $postId = (int) get_the_ID();
        }
        $key = (string) ($args['field'] ?? '');
        $registry = self::service(CollectionRegistry::class);
        $formatter = self::service(FieldFormatter::class);
        if ($postId <= 0 || $key === '' || ! $registry instanceof CollectionRegistry || ! $formatter instanceof FieldFormatter) {
            return null;
        }
        $preset = $registry->byPostType((string) get_post_type($postId));
        if ($preset === null) {
            return null;
        }
        if ($key === 'title') {
            return get_the_title($postId);
        }
        if ($key === 'price') {
            return $formatter->price($preset, $postId);
        }
        if (str_starts_with($key, 'terms:')) {
            return $formatter->terms($preset, substr($key, 6), $postId);
        }
        $field = $preset->field($key);
        if ($field === null) {
            return null;
        }
        $value = $formatter->field($preset, $field, $postId);
        if ($field->type === FieldType::Bool) {
            $label = (string) ($args['label'] ?? '');
            return $value !== '' && $value !== '0' && $value !== 'Não' ? ($label !== '' ? $label : $value) : '';
        }
        return $value;
    }

    /**
     * Um bloco ligado ao Core cujo valor ficou vazio não é impresso.
     *
     * @param array<string, mixed> $block
     */
    public function dropEmptyBoundBlocks(string $content, array $block): string
    {
        $bindings = $block['attrs']['metadata']['bindings'] ?? null;
        if (! is_array($bindings) || $content === '') {
            return $content;
        }
        foreach ($bindings as $attribute => $binding) {
            $source = (string) ($binding['source'] ?? '');
            if (! str_starts_with($source, 'flashsite/') || ! in_array($attribute, ['content', 'text'], true)) {
                continue;
            }
            if (trim(wp_strip_all_tags($content)) === '') {
                return '';
            }
        }
        return $content;
    }

    public function registerBlocks(): void
    {
        $base = FLASHSITE_CORE_PATH . 'assets/blocks/';
        $deps = ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render'];
        wp_register_script('flashsite-blocks-editor', FLASHSITE_CORE_URL . 'assets/blocks/editor.js', $deps, FLASHSITE_CORE_VERSION, true);
        wp_register_script('flashsite-form-view', FLASHSITE_CORE_URL . 'assets/blocks/form/view.js', [], FLASHSITE_CORE_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
        wp_register_style('flashsite-blocks', FLASHSITE_CORE_URL . 'assets/blocks/blocks.css', [], FLASHSITE_CORE_VERSION);

        foreach (['form', 'form-field', 'hours', 'map'] as $name) {
            if (is_file($base . $name . '/block.json')) {
                register_block_type($base . $name);
            }
        }
    }

    public function registerRoutes(): void
    {
        register_rest_route(self::REST_NS, '/form', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [new FormHandler(), 'handle'],
        ]);
    }

    public static function service(string $id): mixed
    {
        $application = Plugin::instance()->application();
        if ($application === null || ! $application->container()->has($id)) {
            return null;
        }
        return $application->container()->make($id);
    }
}
