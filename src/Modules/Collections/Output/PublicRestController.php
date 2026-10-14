<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Output;

use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\CollectionPublicSerializer;
use FlashSite\Core\Domain\Collections\CollectionQuery;
use FlashSite\Core\Domain\Collections\OrderResolver;
use FlashSite\Core\Domain\Collections\TemporalResolver;

/**
 * REST pública, só leitura, só itens publicados e só campos públicos.
 *
 *   GET /wp-json/flashsite/v1/public/collections
 *   GET /wp-json/flashsite/v1/public/collections/{type}?per_page=12&page=1&featured=1
 *   GET /wp-json/flashsite/v1/public/collections/{type}/{id}
 *
 * @since 2.6.0
 */
final class PublicRestController
{
    private const NAMESPACE = 'flashsite/v1';

    /** @var array<string, CollectionPresetInterface> */
    private array $presets = [];

    public function __construct(private CollectionPublicSerializer $serializer) {}

    /** @param array<string, CollectionPresetInterface> $presets */
    public function register(array $presets): void
    {
        $this->presets = $presets;
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(self::NAMESPACE, '/public/collections', [
            'methods' => 'GET',
            'callback' => [$this, 'index'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::NAMESPACE, '/public/collections/(?P<type>[a-z0-9_]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'list'],
            'permission_callback' => '__return_true',
            'args' => [
                'per_page' => ['default' => 12, 'sanitize_callback' => 'absint'],
                'page' => ['default' => 1, 'sanitize_callback' => 'absint'],
                'featured' => ['default' => 0, 'sanitize_callback' => 'absint'],
                'orderby' => ['default' => '', 'sanitize_callback' => 'sanitize_text_field'],
                'order' => ['default' => 'ASC', 'sanitize_callback' => 'sanitize_text_field'],
                'when' => ['default' => '', 'sanitize_callback' => 'sanitize_key'],
            ],
        ]);
        register_rest_route(self::NAMESPACE, '/public/collections/(?P<type>[a-z0-9_]+)/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'show'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function index(): mixed
    {
        $data = [];
        foreach ($this->presets as $preset) {
            $data[] = [
                'type' => $preset->key(),
                'label' => $preset->labels()['plural'],
                'archive' => (string) get_post_type_archive_link($preset->postType()),
                'endpoint' => rest_url(self::NAMESPACE . '/public/collections/' . $preset->key()),
            ];
        }
        return rest_ensure_response($data);
    }

    public function list($request): mixed
    {
        $preset = $this->presets[(string) $request['type']] ?? null;
        if ($preset === null) {
            return new \WP_Error('flashsite_collection_not_found', 'Coleção inexistente ou inativa.', ['status' => 404]);
        }
        $args = [
            'post_type' => $preset->postType(),
            'post_status' => 'publish',
            'posts_per_page' => max(1, min(50, (int) $request['per_page'])),
            'paged' => max(1, (int) $request['page']),
        ];
        $featured = $preset->field((string) $preset->setting('featured_field', 'destaque'));
        if ((int) $request['featured'] === 1 && $featured !== null) {
            $args['meta_query'] = [['key' => $featured->metaKey(), 'value' => '1']];
        }
        // @since 3.4.0: orderby = preset | @date | @title | @menu_order | field:<campo público>; when = upcoming | past | all.
        // Sem estes parâmetros a lista é a de sempre.
        $when = TemporalResolver::normalizeWhen($request['when']);
        $orderby = (string) $request['orderby'];
        $rules = $orderby === 'preset'
            ? OrderResolver::rulesFor($preset, $when)
            : ($orderby !== '' ? OrderResolver::fromToken($preset, $orderby, (string) $request['order']) : []);
        $args = CollectionQuery::apply($args, $preset, $when, $rules);
        $query = new \WP_Query($args);
        $items = array_map(fn (\WP_Post $post): array => $this->serializer->item($preset, $post), $query->posts);

        $response = rest_ensure_response($items);
        if (is_object($response) && method_exists($response, 'header')) {
            $response->header('X-WP-Total', (string) (int) $query->found_posts);
            $response->header('X-WP-TotalPages', (string) (int) $query->max_num_pages);
        }
        return $response;
    }

    public function show($request): mixed
    {
        $preset = $this->presets[(string) $request['type']] ?? null;
        $post = get_post((int) $request['id']);
        if ($preset === null || ! $post instanceof \WP_Post || $post->post_type !== $preset->postType() || $post->post_status !== 'publish' || post_password_required($post)) {
            return new \WP_Error('flashsite_item_not_found', 'Item não encontrado.', ['status' => 404]);
        }
        return rest_ensure_response($this->serializer->item($preset, $post));
    }
}
