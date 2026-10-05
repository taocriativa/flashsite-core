<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Output;

use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\SchemaOrgBuilder;

/**
 * Saída pública das Coleções:
 * - Dynamic Tags Elementor (grupo "FlashSite · Coleções"), V3 e Atomic;
 * - Query IDs para o Loop Grid do Elementor Pro (flashsite_featured, flashsite_available);
 * - shortcodes de alternativa [flashsite_collection] e [flashsite_item];
 * - JSON-LD por item (via Rank Math quando ativo, senão wp_head).
 *
 * O Core fornece dados; o design é sempre do Elementor.
 *
 * @since 2.6.0
 */
final class CollectionsOutput
{
    /** @var list<class-string> */
    public const TAG_CLASSES = [
        Elementor\ItemFieldTag::class,
        Elementor\ItemPriceTag::class,
        Elementor\ItemAreaTag::class,
        Elementor\ItemTermsTag::class,
        Elementor\ItemStatusTag::class,
        Elementor\ItemTypologyTag::class,
        Elementor\ItemZoneTag::class,
        Elementor\ItemImageTag::class,
        Elementor\ItemGalleryTag::class,
        Elementor\ItemUrlTag::class,
    ];

    /** @var array<string, CollectionPresetInterface> */
    private array $presets = [];

    public function __construct(
        private CollectionRegistry $registry,
        private FieldFormatter $formatter,
        private SchemaOrgBuilder $schema,
    ) {}

    /** @param array<string, CollectionPresetInterface> $presets ativos */
    public function register(array $presets): void
    {
        $this->presets = $presets;
        add_shortcode('flashsite_collection', [$this, 'renderCollectionShortcode']);
        add_shortcode('flashsite_item', [$this, 'renderItemShortcode']);
        add_action('elementor/dynamic_tags/register', [$this, 'registerTags']);
        add_action('elementor/query/flashsite_featured', [$this, 'queryFeatured']);
        add_action('elementor/query/flashsite_available', [$this, 'queryAvailable']);
        add_filter('rank_math/json_ld', [$this, 'rankMathJsonLd'], 99, 2);
        add_action('wp_head', [$this, 'printJsonLd'], 30);
    }

    public static function loadTagClasses(): void
    {
        require_once __DIR__ . '/Elementor/CollectionTagBase.php';
        require_once __DIR__ . '/Elementor/ItemFieldTag.php';
        require_once __DIR__ . '/Elementor/ShortcutTags.php';
        require_once __DIR__ . '/Elementor/MediaTags.php';
    }

    public function registerTags($dynamicTags): void
    {
        $tagBaseExists = class_exists('\\Elementor\\Core\\DynamicTags\\Data_Tag') || class_exists('\\Elementor\\Core\\DynamicTags\\Tag');
        if (! $tagBaseExists || ! is_object($dynamicTags) || ! method_exists($dynamicTags, 'register') || $this->presets === []) {
            return;
        }
        self::loadTagClasses();
        if (method_exists($dynamicTags, 'register_group')) {
            $dynamicTags->register_group(Elementor\CollectionTagBase::GROUP, ['title' => 'FlashSite · Coleções']);
        }
        foreach (self::TAG_CLASSES as $class) {
            if (class_exists($class)) {
                $dynamicTags->register(new $class());
            }
        }
    }

    /** Loop Grid › Query ID "flashsite_featured": só itens com destaque. */
    public function queryFeatured($query): void
    {
        $preset = $this->presetForQuery($query);
        $field = $preset !== null ? $preset->field((string) $preset->setting('featured_field', 'destaque')) : null;
        if ($field === null) {
            return;
        }
        $metaQuery = (array) $query->get('meta_query');
        $metaQuery[] = ['key' => $field->metaKey(), 'value' => '1'];
        $query->set('meta_query', $metaQuery);
    }

    /** Loop Grid › Query ID "flashsite_available": esconde vendidos/arrendados (settings.unavailable_terms). */
    public function queryAvailable($query): void
    {
        $preset = $this->presetForQuery($query);
        $config = $preset?->setting('unavailable_terms');
        if ($preset === null || ! is_array($config) || ! isset($config['taxonomy'], $config['terms'])) {
            return;
        }
        $taxQuery = (array) $query->get('tax_query');
        $taxQuery[] = [
            'taxonomy' => $preset->postType() . '_' . (string) $config['taxonomy'],
            'field' => 'slug',
            'terms' => array_values((array) $config['terms']),
            'operator' => 'NOT IN',
        ];
        $query->set('tax_query', $taxQuery);
    }

    /**
     * [flashsite_collection type="imovel" limit="6" featured="1" available="1" orderby="date|price|title" order="DESC"]
     *
     * HTML mínimo e sem estilos impostos (classes fs-collection*). O design faz-se no Elementor.
     *
     * @param array<string, mixed>|string $atts
     */
    public function renderCollectionShortcode($atts = []): string
    {
        $atts = shortcode_atts([
            'type' => '',
            'limit' => '6',
            'featured' => '0',
            'available' => '0',
            'orderby' => 'date',
            'order' => 'DESC',
            'class' => '',
        ], is_array($atts) ? $atts : [], 'flashsite_collection');

        $preset = $this->presets[sanitize_key((string) $atts['type'])] ?? null;
        if ($preset === null) {
            return '';
        }

        $args = [
            'post_type' => $preset->postType(),
            'post_status' => 'publish',
            'posts_per_page' => max(1, min(48, absint($atts['limit']))),
            'order' => strtoupper((string) $atts['order']) === 'ASC' ? 'ASC' : 'DESC',
            'no_found_rows' => true,
        ];
        $orderby = sanitize_key((string) $atts['orderby']);
        if ($orderby === 'price' && $preset->field('preco') !== null) {
            $args['meta_key'] = $preset->field('preco')->metaKey();
            $args['orderby'] = 'meta_value_num';
        } else {
            $args['orderby'] = in_array($orderby, ['date', 'title', 'menu_order', 'rand'], true) ? $orderby : 'date';
        }

        $query = new \WP_Query();
        $query->parse_query($args);
        if ((string) $atts['featured'] === '1') {
            $this->queryFeatured($query);
        }
        if ((string) $atts['available'] === '1') {
            $this->queryAvailable($query);
        }
        $posts = $query->get_posts();
        if ($posts === []) {
            return '';
        }

        $html = sprintf('<div class="fs-collection fs-collection--%s %s">', esc_attr($preset->key()), esc_attr((string) $atts['class']));
        foreach ($posts as $post) {
            $html .= $this->renderCard($preset, $post);
        }
        return $html . '</div>';
    }

    /**
     * [flashsite_item field="preco" id="123" money="cents|auto" fallback=""]
     * field aceita "tax:estado" para mostrar termos.
     *
     * @param array<string, mixed>|string $atts
     */
    public function renderItemShortcode($atts = []): string
    {
        $atts = shortcode_atts(['field' => '', 'id' => '', 'money' => FieldFormatter::MONEY_CENTS, 'fallback' => ''], is_array($atts) ? $atts : [], 'flashsite_item');
        $postId = absint($atts['id']) ?: (int) get_the_ID();
        $preset = $postId > 0 ? $this->registry->byPostType((string) get_post_type($postId)) : null;
        if ($preset === null || ! isset($this->presets[$preset->key()]) || get_post_status($postId) !== 'publish') {
            return esc_html((string) $atts['fallback']);
        }

        $key = sanitize_text_field((string) $atts['field']);
        $style = (string) $atts['money'] === FieldFormatter::MONEY_AUTO ? FieldFormatter::MONEY_AUTO : FieldFormatter::MONEY_CENTS;
        if (str_starts_with($key, 'tax:')) {
            $value = $this->formatter->terms($preset, substr($key, 4), $postId);
        } elseif ($key === 'preco') {
            $value = $this->formatter->price($preset, $postId, $style);
        } else {
            $field = $preset->field($key);
            $value = $field !== null ? $this->formatter->field($preset, $field, $postId, $style) : '';
        }
        return esc_html($value !== '' ? $value : (string) $atts['fallback']);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function rankMathJsonLd($data, $jsonld = null)
    {
        $item = $this->currentItemSchema();
        if ($item !== null && is_array($data)) {
            unset($item['@context']);
            $data['flashsiteItem'] = $item;
        }
        return $data;
    }

    public function printJsonLd(): void
    {
        if (defined('RANK_MATH_VERSION')) {
            return; // O Rank Math inclui o item no grafo dele (rankMathJsonLd).
        }
        $item = $this->currentItemSchema();
        if ($item === null) {
            return;
        }
        echo "\n<script type=\"application/ld+json\" class=\"flashsite-collection-schema\">" . wp_json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
    }

    /** @return array<string, mixed>|null */
    private function currentItemSchema(): ?array
    {
        if (! is_singular()) {
            return null;
        }
        $post = get_queried_object();
        if (! $post instanceof \WP_Post || $post->post_status !== 'publish') {
            return null;
        }
        $preset = $this->registry->byPostType($post->post_type);
        if ($preset === null || ! isset($this->presets[$preset->key()])) {
            return null;
        }
        return $this->schema->build($preset, $post);
    }

    private function renderCard(CollectionPresetInterface $preset, \WP_Post $post): string
    {
        $link = esc_url((string) get_permalink($post));
        $image = get_the_post_thumbnail($post, 'medium_large', ['class' => 'fs-collection__image', 'loading' => 'lazy']);
        $price = $preset->field('preco') !== null ? $this->formatter->price($preset, $post->ID) : '';

        $meta = [];
        foreach ($preset->taxonomies() as $taxonomy) {
            if (! $taxonomy->adminColumn || $taxonomy->auto) {
                continue;
            }
            $text = $this->formatter->terms($preset, $taxonomy->key, $post->ID);
            if ($text !== '') {
                $meta[] = sprintf('<span class="fs-collection__meta fs-collection__meta--%s">%s</span>', esc_attr($taxonomy->key), esc_html($text));
            }
        }

        return sprintf(
            '<article class="fs-collection__item"><a class="fs-collection__link" href="%1$s">%2$s<h3 class="fs-collection__title">%3$s</h3></a>%4$s%5$s</article>',
            $link,
            (string) $image,
            esc_html(get_the_title($post)),
            $price !== '' ? '<p class="fs-collection__price">' . esc_html($price) . '</p>' : '',
            $meta !== [] ? '<p class="fs-collection__details">' . implode(' ', $meta) . '</p>' : ''
        );
    }

    private function presetForQuery($query): ?CollectionPresetInterface
    {
        if (! is_object($query) || ! method_exists($query, 'get')) {
            return null;
        }
        foreach ((array) $query->get('post_type') as $postType) {
            $preset = $this->registry->byPostType((string) $postType);
            if ($preset !== null && isset($this->presets[$preset->key()])) {
                return $preset;
            }
        }
        // Loop sem post type explícito: se só houver uma coleção ativa, aplica-se a ela.
        return count($this->presets) === 1 ? reset($this->presets) : null;
    }
}
