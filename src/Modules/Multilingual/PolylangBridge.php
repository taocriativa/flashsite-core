<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Multilingual;

use FlashSite\Core\Domain\Collections\CollectionSettings;

/**
 * Ligação ao Polylang (@since 3.2.0-beta.4). Só é criada com o Polylang ativo.
 *
 * - Coleções com "itens por idioma" passam a ser traduzíveis no Polylang (o CPT entra na
 *   lista dele). As compartilhadas ficam fora: um item só, visto em todos os idiomas.
 * - REST flashsite/v1/translations (quem pode editar): idioma e traduções de um conteúdo e
 *   criação de uma tradução a partir do original, com os campos, a imagem e o Elementor
 *   copiados. Serve o MCP e quem monta o site sem abrir o painel do Polylang.
 */
final class PolylangBridge
{
    /** Metadados que não passam para a tradução. */
    private const SKIP_META = ['_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_elementor_css', '_elementor_page_assets', '_elementor_element_cache', '_pll_strings_translations'];

    /** @param \Closure(): array<string, string> $postTypes chave da coleção → post type */
    public function __construct(private CollectionSettings $settings, private \Closure $postTypes) {}

    public function register(): void
    {
        add_filter('pll_get_post_types', [$this, 'translatablePostTypes'], 10, 2);
        add_action('rest_api_init', [$this, 'registerRest']);
    }

    /**
     * @param array<string, string> $postTypes
     * @return array<string, string>
     */
    public function translatablePostTypes(array $postTypes, bool $isSettings = false): array
    {
        $map = ($this->postTypes)();
        foreach ($this->settings->perLanguageKeys() as $key) {
            if (isset($map[$key])) {
                $postTypes[$map[$key]] = $map[$key];
            }
        }
        return $postTypes;
    }

    public function registerRest(): void
    {
        register_rest_route('flashsite/v1', '/translations/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [$this, 'restGet'], 'permission_callback' => [$this, 'canEdit']],
            ['methods' => 'POST', 'callback' => [$this, 'restSetLanguage'], 'permission_callback' => [$this, 'canEdit']],
        ]);
        register_rest_route('flashsite/v1', '/translations', [
            'methods' => 'POST',
            'callback' => [$this, 'restTranslate'],
            'permission_callback' => [$this, 'canEditSource'],
        ]);
    }

    /** @param \WP_REST_Request $request */
    public function canEdit($request): bool
    {
        return current_user_can('edit_post', (int) $request['id']);
    }

    /** @param \WP_REST_Request $request */
    public function canEditSource($request): bool
    {
        $source = (int) ($request->get_json_params()['source'] ?? 0);
        return $source > 0 && current_user_can('edit_post', $source) && current_user_can('edit_posts');
    }

    /** @param \WP_REST_Request $request */
    public function restGet($request): array
    {
        $id = (int) $request['id'];
        return ['id' => $id, 'language' => (string) pll_get_post_language($id, 'slug'), 'translations' => (array) pll_get_post_translations($id)];
    }

    /**
     * Define o idioma de um conteúdo que ainda não tem (ex.: itens criados antes de a coleção ser por idioma).
     *
     * @param \WP_REST_Request $request
     */
    public function restSetLanguage($request): mixed
    {
        $id = (int) $request['id'];
        $language = sanitize_key((string) ($request->get_json_params()['language'] ?? ''));
        if (! in_array($language, $this->languages(), true)) {
            return new \WP_Error('flashsite_language', 'Idioma desconhecido no Polylang.', ['status' => 400]);
        }
        pll_set_post_language($id, $language);
        return $this->restGet($request);
    }

    /**
     * Cria (ou devolve) a tradução de um conteúdo noutro idioma.
     * Corpo: source, language, e opcionais title, slug, status, content, excerpt, meta (chave → valor).
     *
     * @param \WP_REST_Request $request
     */
    public function restTranslate($request): mixed
    {
        $in = (array) $request->get_json_params();
        $source = get_post((int) ($in['source'] ?? 0));
        $language = sanitize_key((string) ($in['language'] ?? ''));
        if (! $source instanceof \WP_Post) {
            return new \WP_Error('flashsite_source', 'Original não encontrado.', ['status' => 404]);
        }
        if (! in_array($language, $this->languages(), true)) {
            return new \WP_Error('flashsite_language', 'Idioma desconhecido no Polylang.', ['status' => 400]);
        }
        if (! pll_is_translated_post_type($source->post_type)) {
            return new \WP_Error('flashsite_not_translatable', 'Este tipo de conteúdo não é traduzível (coleção compartilhada?).', ['status' => 400]);
        }
        if ((string) pll_get_post_language($source->ID, 'slug') === '') {
            pll_set_post_language($source->ID, (string) pll_default_language('slug'));
        }
        if ((string) pll_get_post_language($source->ID, 'slug') === $language) {
            return new \WP_Error('flashsite_same_language', 'O original já está neste idioma.', ['status' => 400]);
        }
        $translations = (array) pll_get_post_translations($source->ID);
        if (isset($translations[$language])) {
            return ['id' => (int) $translations[$language], 'language' => $language, 'translations' => $translations, 'created' => false];
        }

        $status = in_array($in['status'] ?? '', ['publish', 'draft', 'private'], true) ? (string) $in['status'] : 'draft';
        $id = wp_insert_post(wp_slash([
            'post_type' => $source->post_type,
            'post_status' => $status,
            'post_title' => isset($in['title']) ? sanitize_text_field((string) $in['title']) : $source->post_title,
            'post_name' => isset($in['slug']) ? sanitize_title((string) $in['slug']) : '',
            'post_content' => isset($in['content']) ? wp_kses_post((string) $in['content']) : $source->post_content,
            'post_excerpt' => isset($in['excerpt']) ? sanitize_textarea_field((string) $in['excerpt']) : $source->post_excerpt,
            'post_parent' => $source->post_parent,
            'menu_order' => $source->menu_order,
            'post_author' => get_current_user_id() ?: $source->post_author,
        ]), true);
        if (is_wp_error($id)) {
            return $id;
        }

        foreach ((array) get_post_meta($source->ID) as $key => $values) {
            if (in_array($key, self::SKIP_META, true) || str_starts_with((string) $key, '_pll')) {
                continue;
            }
            foreach ((array) $values as $value) {
                add_post_meta($id, (string) $key, wp_slash(maybe_unserialize($value)));
            }
        }
        foreach ((array) ($in['meta'] ?? []) as $key => $value) {
            if (is_string($key) && current_user_can('edit_post_meta', $id, $key)) {
                update_post_meta($id, $key, wp_slash($value));
            }
        }
        foreach (get_object_taxonomies($source->post_type) as $taxonomy) {
            if (in_array($taxonomy, ['language', 'post_translations'], true)) {
                continue;
            }
            $terms = wp_get_object_terms($source->ID, $taxonomy, ['fields' => 'ids']);
            if (is_array($terms) && $terms !== []) {
                wp_set_object_terms($id, array_map('intval', $terms), $taxonomy);
            }
        }

        pll_set_post_language($id, $language);
        $translations[(string) pll_get_post_language($source->ID, 'slug')] = $source->ID;
        $translations[$language] = $id;
        pll_save_post_translations($translations);

        return ['id' => $id, 'language' => $language, 'translations' => (array) pll_get_post_translations($id), 'created' => true];
    }

    /** @return list<string> */
    private function languages(): array
    {
        return array_values(array_map('strval', (array) pll_languages_list(['fields' => 'slug'])));
    }
}
