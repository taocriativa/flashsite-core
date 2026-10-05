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
 * As páginas são encontradas pelo título (settings.model_pages do preset) e podem ficar em
 * rascunho: nunca são publicadas. Dentro delas, as tags "Item · …" com "Item atual" mostram
 * o item que está a ser renderizado (ou um item real de exemplo, no editor).
 *
 * @since 2.6.0
 */
final class ModelPages
{
    /** @var array<string, CollectionPresetInterface> */
    private array $presets = [];

    /** @var array<string, int> cache "preset|papel" => page ID */
    private array $resolved = [];

    private ?CollectionPresetInterface $currentPreset = null;

    /** @param array<string, CollectionPresetInterface> $presets */
    public function register(array $presets): void
    {
        $this->presets = $presets;
        if ($presets === []) {
            return;
        }
        add_filter('template_include', [$this, 'templateInclude'], 99);
        add_action('pre_get_posts', [$this, 'archiveQuery']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        CollectionTagBase::setPreviewResolver(fn (int $postId): int => $this->previewItemFor($postId));
    }

    public function pageId(CollectionPresetInterface $preset, string $role): int
    {
        $cacheKey = $preset->key() . '|' . $role;
        if (isset($this->resolved[$cacheKey])) {
            return $this->resolved[$cacheKey];
        }
        $titles = (array) $preset->setting('model_pages', []);
        $title = (string) ($titles[$role] ?? '');
        $id = 0;
        if ($title !== '') {
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
        if ($this->presetForRequest() === null) {
            return;
        }
        wp_enqueue_style('flashsite-core-collections-front', FLASHSITE_CORE_URL . 'assets/frontend/css/fsc-collections.css', [], FLASHSITE_CORE_VERSION);
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
            $this->renderPagination();
        } else {
            printf(
                '<div class="fs-collection-empty"><p>%s</p><a href="%s">%s</a></div>',
                esc_html((string) ($preset->labels()['not_found'] ?? 'Nada encontrado.') . ' Experimente outros filtros.'),
                esc_url((string) get_post_type_archive_link($preset->postType())),
                'Ver todos'
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
            printf('<option value="">%s</option>', esc_html('Todos'));
            $this->renderOptions($terms, 0, $current, $taxonomy->hierarchical, 0);
            echo '</select></label>';
        }
        echo '<div class="fs-collection-filters__actions"><button type="submit" class="fs-collection-filters__submit">Filtrar</button>';
        if ($active) {
            printf('<a class="fs-collection-filters__reset" href="%s">Limpar</a>', esc_url((string) get_post_type_archive_link($preset->postType())));
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

    private function renderPagination(): void
    {
        $links = paginate_links(['type' => 'list', 'prev_text' => '← Anteriores', 'next_text' => 'Seguintes →']);
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
