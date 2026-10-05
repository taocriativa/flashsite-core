<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Output;

use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\TaxonomyDefinition;
use FlashSite\Core\Modules\Collections\Output\Elementor\CollectionTagBase;

/**
 * Páginas modelo: o design das páginas de uma coleção é feito em páginas normais do Elementor
 * (montáveis pelo MCP) e o Core usa-as como modelo, sem Theme Builder.
 *
 *   item            página de cada item (/imoveis/<slug>/)
 *   card            cartão repetido por item na listagem
 *   archive_top     topo da listagem (/imoveis/ e /imoveis/<taxonomia>/<termo>/)
 *   archive_bottom  fim da listagem
 *
 * Modelos do site inteiro (independentes das coleções):
 *   "Modelo · 404"        página de erro 404
 *   "Modelo · Cabeçalho"  + "Modelo · Rodapé" envolvem as páginas de texto feitas no editor do
 *                         WordPress (ex.: Política de Privacidade com [flashsite_privacy_policy])
 *   Popups               quantos forem precisos, cada um com gatilho, frequência e páginas
 *                         próprios (título antigo: "Modelo · Popup" 30 s, "Modelo · Popup saída")
 *
 * O papel de cada página escolhe-se nas Definições da página do Elementor (ModelRoles,
 * "FlashSite · Página modelo"), também via MCP. Alternativa antiga: o título da página
 * (settings.model_pages do preset / "Modelo · …"). As páginas podem ficar em rascunho. Dentro delas, as tags "Item · …" com "Item atual" mostram
 * o item que está a ser renderizado (ou um item real de exemplo, no editor).
 *
 * @since 2.6.0
 */
final class ModelPages
{
    public const SITE_404 = 'Modelo · 404';
    public const SITE_HEADER = 'Modelo · Cabeçalho';
    public const SITE_FOOTER = 'Modelo · Rodapé';
    public const SITE_POPUP = 'Modelo · Popup';
    public const SITE_POPUP_EXIT = 'Modelo · Popup saída';

    /** @var array<string, int> */
    private array $sitePages = [];

    private string $siteMode = '';

    /** @var array<string, CollectionPresetInterface> */
    private array $presets = [];

    /** @var array<string, int> cache "preset|papel" => page ID */
    private array $resolved = [];

    private ?CollectionPresetInterface $currentPreset = null;

    /** @var list<array{id: int, settings: array<string, mixed>}>|null */
    private ?array $popups = null;

    private const SITE_ROLES = [
        self::SITE_404 => 'site:404',
        self::SITE_HEADER => 'site:header',
        self::SITE_FOOTER => 'site:footer',
    ];

    public function __construct(private ModelRoles $roles = new ModelRoles()) {}

    /** @param array<string, CollectionPresetInterface> $presets */
    public function register(array $presets): void
    {
        $this->presets = $presets;
        $this->roles->register($presets);
        // Modelos do site (404, cabeçalho/rodapé de páginas de texto) funcionam sem coleções.
        add_filter('template_include', [$this, 'templateInclude'], 99);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_enqueue_scripts', [$this, 'primeElementorAssets'], 15);
        add_action('wp_footer', [$this, 'renderPopups'], 5);
        if ($presets === []) {
            return;
        }
        add_action('pre_get_posts', [$this, 'archiveQuery']);
        CollectionTagBase::setPreviewResolver(fn (int $postId): int => $this->previewItemFor($postId));
    }

    public function sitePageId(string $title): int
    {
        if (! isset($this->sitePages[$title]) && isset(self::SITE_ROLES[$title]) && ($byRole = $this->roles->first(self::SITE_ROLES[$title])) > 0) {
            $this->sitePages[$title] = $byRole;
        }
        if (! isset($this->sitePages[$title])) {
            $ids = get_posts([
                'post_type' => 'page',
                'post_status' => ['draft', 'private', 'publish', 'pending'],
                'title' => $title,
                'posts_per_page' => 1,
                'fields' => 'ids',
                'orderby' => 'ID',
                'order' => 'ASC',
                'suppress_filters' => true,
            ]);
            $this->sitePages[$title] = (int) ($ids[0] ?? 0);
        }
        return $this->sitePages[$title];
    }

    /** 404 ou página de texto (não Elementor) a envolver com cabeçalho/rodapé. */
    private function siteModeForRequest(): string
    {
        if ($this->siteMode !== '') {
            return $this->siteMode === 'none' ? '' : $this->siteMode;
        }
        $mode = 'none';
        if (is_404() && $this->sitePageId(self::SITE_404) > 0) {
            $mode = '404';
        } elseif (is_page() && ! $this->isElementorPage((int) get_queried_object_id())
            && ($this->sitePageId(self::SITE_HEADER) > 0 || $this->sitePageId(self::SITE_FOOTER) > 0)) {
            $mode = 'page';
        }
        $this->siteMode = $mode;
        return $mode === 'none' ? '' : $mode;
    }

    private function isElementorPage(int $postId): bool
    {
        return $postId > 0 && get_post_meta($postId, '_elementor_edit_mode', true) === 'builder';
    }

    public function render404(): void
    {
        $this->renderModel($this->sitePageId(self::SITE_404), 0);
    }

    /** Página de texto: cabeçalho do site, título + conteúdo do editor, rodapé do site. */
    public function renderTextPage(): void
    {
        $this->renderModel($this->sitePageId(self::SITE_HEADER), 0);
        echo '<article class="fs-text-page"><div class="fs-text-page__inner">';
        while (have_posts()) {
            the_post();
            echo '<h1 class="fs-text-page__title">' . esc_html(get_the_title()) . '</h1>';
            echo '<div class="fs-text-page__content">';
            // Página de privacidade do WordPress: usa o texto gerido em FlashSite › Política de Privacidade.
            $policy = class_exists('\\FlashSite\\Core\\Modules\\PrivacyPolicy\\PrivacyPolicyModule')
                ? \FlashSite\Core\Modules\PrivacyPolicy\PrivacyPolicyModule::getContent()
                : '';
            if ((int) get_the_ID() === (int) get_option('wp_page_for_privacy_policy') && trim($policy) !== '') {
                printf('<p class="fs-text-page__updated">Última atualização: %s</p>', esc_html(\FlashSite\Core\Modules\PrivacyPolicy\PrivacyPolicyModule::getUpdatedDate()));
                echo wp_kses_post(wpautop($policy));
            } else {
                the_content();
            }
            echo '</div>';
        }
        echo '</div></article>';
        $this->renderModel($this->sitePageId(self::SITE_FOOTER), 0);
    }

    public function pageId(CollectionPresetInterface $preset, string $role): int
    {
        $cacheKey = $preset->key() . '|' . $role;
        if (isset($this->resolved[$cacheKey])) {
            return $this->resolved[$cacheKey];
        }
        $id = $this->roles->first($preset->key() . ':' . $role);
        $titles = (array) $preset->setting('model_pages', []);
        $title = (string) ($titles[$role] ?? '');
        if ($id === 0 && $title !== '') {
            $ids = get_posts([
                'post_type' => 'page',
                'post_status' => ['draft', 'private', 'publish', 'pending'],
                'title' => $title,
                'posts_per_page' => 1,
                'fields' => 'ids',
                'orderby' => 'ID',
                'order' => 'ASC',
                'suppress_filters' => true,
            ]);
            $id = (int) ($ids[0] ?? 0);
        }
        return $this->resolved[$cacheKey] = $id;
    }

    public function templateInclude(string $template): string
    {
        $siteMode = $this->siteModeForRequest();
        if ($siteMode === '404') {
            return FLASHSITE_CORE_PATH . 'templates/collections/404.php';
        }
        if ($siteMode === 'page') {
            return FLASHSITE_CORE_PATH . 'templates/collections/page.php';
        }
        $preset = $this->presetForRequest();
        if ($preset === null) {
            return $template;
        }
        if (is_singular($preset->postType())) {
            if ($this->pageId($preset, 'item') === 0) {
                return $template;
            }
            $this->currentPreset = $preset;
            return FLASHSITE_CORE_PATH . 'templates/collections/single.php';
        }
        if ($this->pageId($preset, 'card') === 0) {
            return $template;
        }
        $this->currentPreset = $preset;
        return FLASHSITE_CORE_PATH . 'templates/collections/archive.php';
    }

    /** Listagem: n.º por página, ordenação e (opcional) esconder indisponíveis. */
    public function archiveQuery($query): void
    {
        if (! is_object($query) || is_admin() || ! $query->is_main_query()) {
            return;
        }
        foreach ($this->presets as $preset) {
            if (! $query->is_post_type_archive($preset->postType()) && ! $this->isPresetTaxQuery($query, $preset)) {
                continue;
            }
            $query->set('post_type', $preset->postType());
            $query->set('posts_per_page', (int) $preset->setting('archive_per_page', 12));
            $query->set('orderby', ['menu_order' => 'ASC', 'date' => 'DESC']);
            return;
        }
    }

    public function enqueueAssets(): void
    {
        if ($this->popups() !== []) {
            wp_enqueue_style('flashsite-core-popup', FLASHSITE_CORE_URL . 'assets/frontend/css/fsc-popup.css', [], FLASHSITE_CORE_VERSION);
            wp_enqueue_script('flashsite-core-popup', FLASHSITE_CORE_URL . 'assets/frontend/js/fsc-popup.js', [], FLASHSITE_CORE_VERSION, ['in_footer' => true, 'strategy' => 'defer']);
        }
        if ($this->presetForRequest() === null && $this->siteModeForRequest() === '') {
            return;
        }
        wp_enqueue_style('flashsite-core-collections-front', FLASHSITE_CORE_URL . 'assets/frontend/css/fsc-collections.css', [], FLASHSITE_CORE_VERSION);
    }

    /**
     * O Elementor 4 só gera/carrega o CSS atómico (base/global/local-<id>-frontend-*.css) das
     * páginas que "renderizam" antes do <head> (hook elementor/post/render) e o CSS de post de
     * documentos conhecidos no enqueue. As páginas modelo são renderizadas no corpo, por isso
     * são anunciadas aqui, antes do enqueue de estilos do Elementor.
     */
    public function primeElementorAssets(): void
    {
        if (! class_exists('\\Elementor\\Plugin')) {
            return;
        }
        $siteMode = $this->siteModeForRequest();
        $preset = $this->presetForRequest();
        if ($siteMode === '404') {
            $ids = [$this->sitePageId(self::SITE_404)];
        } elseif ($siteMode === 'page') {
            $ids = [$this->sitePageId(self::SITE_HEADER), $this->sitePageId(self::SITE_FOOTER)];
        } elseif ($preset !== null) {
            $roles = is_singular($preset->postType()) ? ['item'] : ['archive_top', 'card', 'archive_bottom'];
            $ids = array_map(fn (string $role): int => $this->pageId($preset, $role), $roles);
        } else {
            $ids = [];
        }
        $ids = array_values(array_unique(array_filter(array_merge($ids, array_column($this->popups(), 'id')))));
        if ($ids === []) {
            return;
        }
        foreach ($ids as $id) {
            do_action('elementor/post/render', $id);
            if (class_exists('\\Elementor\\Core\\Files\\CSS\\Post')) {
                \Elementor\Core\Files\CSS\Post::create($id)->enqueue();
            }
        }
        $frontend = \Elementor\Plugin::instance()->frontend;
        $frontend->enqueue_styles();
        $frontend->enqueue_scripts();
    }

    /**
     * Popups desta página: as páginas com papel "Popup" (cada uma com o seu gatilho) e, como
     * alternativa antiga, as páginas "Modelo · Popup" (30 s) e "Modelo · Popup saída" (saída).
     * Não aparecem no editor, nas pré-visualizações nem nas próprias páginas modelo.
     *
     * @return list<array{id: int, settings: array<string, mixed>}>
     */
    public function popups(): array
    {
        if ($this->popups !== null) {
            return $this->popups;
        }
        $this->popups = [];
        if (is_admin() || wp_doing_ajax() || is_feed() || is_embed() || isset($_GET['elementor-preview']) || is_customize_preview()) { // phpcs:ignore WordPress.Security.NonceVerification
            return $this->popups;
        }
        $current = (int) get_queried_object_id();
        if ($current > 0 && (($this->roles->roleOf($current) !== '' && get_post_type($current) === 'page') || str_starts_with((string) get_the_title($current), 'Modelo · '))) {
            return $this->popups;
        }

        $list = [];
        foreach ($this->roles->pages('site:popup') as $id) {
            $list[$id] = $this->roles->popupSettings($id);
        }
        foreach ([self::SITE_POPUP => 'delay', self::SITE_POPUP_EXIT => 'exit'] as $title => $trigger) {
            $id = $this->sitePageId($title);
            if ($id > 0 && ! isset($list[$id])) {
                $list[$id] = ['fs_popup_trigger' => $trigger] + ModelRoles::POPUP_DEFAULTS;
            }
        }

        foreach ($list as $id => $settings) {
            if ($this->popupMatchesPage((string) $settings['fs_popup_where'])) {
                $this->popups[] = ['id' => (int) $id, 'settings' => $settings];
            }
        }
        /** Filtro: devolver [] desliga os popups nesta página. */
        $this->popups = array_values((array) apply_filters('flashsite/site_popups', $this->popups));
        return $this->popups;
    }

    /** @deprecated 2.6.0-beta.7 usar popups() */
    public function popupIds(): array
    {
        return array_column($this->popups(), 'id');
    }

    private function popupMatchesPage(string $where): bool
    {
        $home = is_front_page();
        return match ($where) {
            'home' => $home,
            'not_home' => ! $home,
            'collections' => $this->presetForRequest() !== null,
            default => true,
        };
    }

    /** Escreve os popups (escondidos) no rodapé; o fsc-popup.js abre cada um pelo seu gatilho. */
    public function renderPopups(): void
    {
        $popups = $this->popups();
        if ($popups === []) {
            return;
        }
        echo '<div class="fs-popups">';
        foreach ($popups as $popup) {
            $id = $popup['id'];
            $s = $popup['settings'];
            printf(
                '<dialog class="fs-popup" id="fs-popup-%1$d" data-popup="%1$d" data-trigger="%2$s" data-delay="%3$d" data-scroll="%4$d" data-frequency="%5$s" data-days="%6$d" data-device="%7$s" data-version="%8$s" aria-label="%9$s"><div class="fs-popup__box"><button type="button" class="fs-popup__close" aria-label="Fechar" data-fs-popup-close>&times;</button>',
                $id,
                esc_attr((string) $s['fs_popup_trigger']),
                (int) $s['fs_popup_delay'],
                (int) $s['fs_popup_scroll'],
                esc_attr((string) $s['fs_popup_frequency']),
                (int) $s['fs_popup_days'],
                esc_attr((string) $s['fs_popup_device']),
                esc_attr(substr(md5((string) get_post_field('post_modified', $id)), 0, 8)),
                esc_attr(wp_strip_all_tags(get_the_title($id)))
            );
            $this->renderModel($id, 0);
            echo '</div></dialog>';
        }
        echo '</div>';
    }

    public function currentPreset(): ?CollectionPresetInterface
    {
        return $this->currentPreset ?? $this->presetForRequest();
    }

    /** Página de um item: renderiza a página modelo "item" com o item como contexto. */
    public function renderSingle(): void
    {
        $preset = $this->currentPreset();
        $postId = (int) get_queried_object_id();
        if ($preset === null || $postId === 0) {
            return;
        }
        $this->renderModel($this->pageId($preset, 'item'), $postId);
    }

    /** Listagem: topo, filtros, grelha de cartões, paginação e fim. */
    public function renderArchive(): void
    {
        $preset = $this->currentPreset();
        if ($preset === null) {
            return;
        }
        $this->renderModel($this->pageId($preset, 'archive_top'), 0);

        echo '<section class="fs-collection-archive fs-collection-archive--' . esc_attr($preset->key()) . '">';
        $this->renderFilters($preset);

        $cardId = $this->pageId($preset, 'card');
        if (have_posts()) {
            echo '<div class="fs-collection-grid">';
            while (have_posts()) {
                the_post();
                echo '<div class="fs-collection-grid__item">';
                $this->renderModel($cardId, (int) get_the_ID());
                echo '</div>';
            }
            echo '</div>';
            $this->renderPagination($preset);
        } else {
            printf(
                '<div class="fs-collection-empty"><p>%s</p><a href="%s">%s</a></div>',
                esc_html($this->text($preset, 'empty')),
                esc_url((string) get_post_type_archive_link($preset->postType())),
                esc_html($this->text($preset, 'show_all'))
            );
        }
        echo '</section>';
        wp_reset_postdata();

        $this->renderModel($this->pageId($preset, 'archive_bottom'), 0);
    }

    private function renderModel(int $pageId, int $contextPostId): void
    {
        if ($pageId <= 0 || ! class_exists('\\Elementor\\Plugin')) {
            return;
        }
        CollectionTagBase::setContextPostId($contextPostId);
        // with_css = true: o CSS da página modelo é incluído uma vez por documento.
        echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($pageId, true); // phpcs:ignore WordPress.Security.EscapeOutput
        CollectionTagBase::setContextPostId(0);
    }

    private function renderFilters(CollectionPresetInterface $preset): void
    {
        $taxonomies = array_filter($preset->taxonomies(), static fn (TaxonomyDefinition $t): bool => $t->filterable);
        if ($taxonomies === [] || $preset->setting('archive_filters', true) === false) {
            return;
        }
        $active = false;
        echo '<form class="fs-collection-filters" method="get" action="' . esc_url((string) get_post_type_archive_link($preset->postType())) . '">';
        foreach ($taxonomies as $taxonomy) {
            $name = $taxonomy->taxonomyName($preset->postType());
            $terms = get_terms(['taxonomy' => $name, 'hide_empty' => true]);
            if (! is_array($terms) || $terms === []) {
                continue;
            }
            $current = $this->currentTermSlug($name);
            $active = $active || $current !== '';
            if ($taxonomy->terms !== []) {
                $order = array_flip(array_keys($taxonomy->terms));
                usort($terms, static fn ($a, $b) => ($order[$a->slug] ?? 99) <=> ($order[$b->slug] ?? 99));
            } elseif ($taxonomy->auto && $name !== '') {
                usort($terms, static fn ($a, $b) => (int) $a->term_id <=> (int) $b->term_id);
            }
            printf('<label class="fs-collection-filters__field"><span class="fs-collection-filters__label">%s</span><select name="%s">', esc_html($taxonomy->singularLabel), esc_attr($name));
            printf('<option value="">%s</option>', esc_html($this->text($preset, 'any')));
            $this->renderOptions($terms, 0, $current, $taxonomy->hierarchical, 0);
            echo '</select></label>';
        }
        printf('<div class="fs-collection-filters__actions"><button type="submit" class="fs-collection-filters__submit">%s</button>', esc_html($this->text($preset, 'filter')));
        if ($active) {
            printf('<a class="fs-collection-filters__reset" href="%s">%s</a>', esc_url((string) get_post_type_archive_link($preset->postType())), esc_html($this->text($preset, 'reset')));
        }
        echo '</div></form>';
    }

    /** @param list<object> $terms */
    private function renderOptions(array $terms, int $parent, string $current, bool $hierarchical, int $depth): void
    {
        foreach ($terms as $term) {
            if ($hierarchical && (int) $term->parent !== $parent) {
                continue;
            }
            printf(
                '<option value="%s"%s>%s%s</option>',
                esc_attr((string) $term->slug),
                $current === (string) $term->slug ? ' selected' : '',
                $depth > 0 ? str_repeat('— ', $depth) : '',
                esc_html((string) $term->name)
            );
            if ($hierarchical) {
                $this->renderOptions($terms, (int) $term->term_id, $current, true, $depth + 1);
            }
        }
    }

    /** Textos da listagem: settings.archive_texts do preset, com alternativas genéricas. */
    private function text(CollectionPresetInterface $preset, string $key): string
    {
        $defaults = [
            'empty' => 'Não encontrámos resultados com estes filtros.',
            'show_all' => 'Ver todos',
            'any' => 'Todos',
            'filter' => 'Filtrar',
            'reset' => 'Limpar',
            'prev' => '← Anteriores',
            'next' => 'Seguintes →',
        ];
        $texts = (array) $preset->setting('archive_texts', []);
        $value = $texts[$key] ?? $defaults[$key] ?? '';
        return (string) apply_filters('flashsite/collections/archive_text', $value, $key, $preset->key());
    }

    private function renderPagination(CollectionPresetInterface $preset): void
    {
        $links = paginate_links(['type' => 'list', 'prev_text' => $this->text($preset, 'prev'), 'next_text' => $this->text($preset, 'next')]);
        if (is_string($links) && $links !== '') {
            echo '<nav class="fs-collection-pagination" aria-label="Paginação">' . $links . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
        }
    }

    private function currentTermSlug(string $taxonomy): string
    {
        $value = get_query_var($taxonomy);
        if (is_string($value) && $value !== '') {
            return sanitize_title(explode(',', $value)[0]);
        }
        $object = get_queried_object();
        if (is_object($object) && isset($object->taxonomy) && $object->taxonomy === $taxonomy) {
            return (string) $object->slug;
        }
        return '';
    }

    private function presetForRequest(): ?CollectionPresetInterface
    {
        foreach ($this->presets as $preset) {
            if (is_singular($preset->postType()) || is_post_type_archive($preset->postType())) {
                return $preset;
            }
            foreach ($preset->taxonomies() as $taxonomy) {
                if (is_tax($taxonomy->taxonomyName($preset->postType()))) {
                    return $preset;
                }
            }
        }
        return null;
    }

    private function isPresetTaxQuery($query, CollectionPresetInterface $preset): bool
    {
        foreach ($preset->taxonomies() as $taxonomy) {
            if ($query->is_tax($taxonomy->taxonomyName($preset->postType()))) {
                return true;
            }
        }
        return false;
    }

    /** Se $postId for uma página modelo, devolve o item mais recente da coleção para pré-visualizar. */
    private function previewItemFor(int $postId): int
    {
        if (get_post_type($postId) !== 'page') {
            return 0;
        }
        foreach ($this->presets as $preset) {
            foreach (['item', 'card'] as $role) {
                if ($this->pageId($preset, $role) === $postId) {
                    $ids = get_posts([
                        'post_type' => $preset->postType(),
                        'post_status' => 'publish',
                        'posts_per_page' => 1,
                        'fields' => 'ids',
                    ]);
                    return (int) ($ids[0] ?? 0);
                }
            }
        }
        return 0;
    }
}
