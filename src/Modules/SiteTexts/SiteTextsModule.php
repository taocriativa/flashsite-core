<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\SiteTexts;

use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\Strings;
use FlashSite\Core\Domain\SiteTexts\SiteTextsRegistry;

/**
 * FlashSite › Textos do site (@since 3.1.0): o cliente muda os textos marcados com a tag
 * "Texto do site" sem abrir o Elementor. A lista de campos é lida das páginas do Elementor
 * quando são salvas, quando o Core muda de versão e pelo botão "Atualizar lista".
 * Com vários idiomas (@since 3.2.0-beta.3), a tela tem uma aba por idioma.
 */
final class SiteTextsModule implements ModuleInterface
{
    public const CAP = 'flashsite_manage_business_data';
    public const SLUG = 'flashsite-site-texts';
    private const SCAN_OPTION = 'flashsite_site_texts_scan_version';

    public function __construct(private SiteTextsRegistry $registry) {}

    public function register(): void
    {
        add_action('elementor/dynamic_tags/register', [$this, 'registerTag']);
        add_action('elementor/document/after_save', [$this, 'onDocumentSave'], 10, 1);
        add_action('admin_menu', [$this, 'addPage'], 15);
        add_action('admin_init', [$this, 'maybeRescan']);
        add_action('admin_post_flashsite_site_texts_save', [$this, 'handleSave']);
        add_action('admin_post_flashsite_site_texts_rescan', [$this, 'handleRescan']);
        add_action('wp_footer', [$this, 'printHighlightScript'], 50);
    }

    public function boot(): void {}
    public function isActive(): bool { return true; }
    public function getSlug(): string { return 'site-texts'; }

    public function registerTag($dynamicTags): void
    {
        $tagBaseExists = class_exists('\\Elementor\\Core\\DynamicTags\\Data_Tag') || class_exists('\\Elementor\\Core\\DynamicTags\\Tag');
        if (! $tagBaseExists || ! is_object($dynamicTags) || ! method_exists($dynamicTags, 'register')) {
            return;
        }
        require_once __DIR__ . '/SiteTextTag.php';
        $dynamicTags->register(new SiteTextTag());
    }

    /** @param mixed $document */
    public function onDocumentSave($document): void
    {
        if (is_object($document) && method_exists($document, 'get_main_id')) {
            $this->scanPost((int) $document->get_main_id());
        }
    }

    public function maybeRescan(): void
    {
        if (get_option(self::SCAN_OPTION) !== FLASHSITE_CORE_VERSION) {
            $this->rescanAll();
            update_option(self::SCAN_OPTION, FLASHSITE_CORE_VERSION, false);
        }
    }

    public function scanPost(int $postId): void
    {
        if ($postId <= 0) {
            return;
        }
        $raw = get_post_meta($postId, '_elementor_data', true);
        $data = is_string($raw) ? json_decode($raw, true) : $raw;
        $status = (string) get_post_status($postId);
        $found = is_array($data) && in_array($status, ['publish', 'private'], true) ? SiteTextsRegistry::extract($data) : [];
        $context = Strings::context();
        $this->registry->syncPage($postId, $found, $context !== null ? $context->ofPost($postId) : '');
    }

    /** Relê todas as páginas do Elementor publicadas. */
    public function rescanAll(): int
    {
        $ids = get_posts([
            'post_type' => 'any',
            'post_status' => ['publish', 'private'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_key' => '_elementor_edit_mode',
            'meta_value' => 'builder',
            'suppress_filters' => true,
        ]);
        update_option(SiteTextsRegistry::FIELDS_OPTION, [], false);
        foreach ($ids as $id) {
            $this->scanPost((int) $id);
        }
        return count($ids);
    }

    public function addPage(): void
    {
        add_submenu_page('flashsite-core', 'Textos do site', 'Textos do site', self::CAP, self::SLUG, [$this, 'renderPage']);
    }

    public function renderPage(): void
    {
        if (! current_user_can(self::CAP)) {
            wp_die(esc_html__('Sem permissão.', 'flashsite-core'));
        }
        $languages = $this->registry->languages();
        $requested = isset($_GET['idioma']) ? sanitize_text_field((string) wp_unslash($_GET['idioma'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $language = in_array($requested, $languages, true) ? $requested : $languages[0];
        $groups = $this->groupedFields($language);
        $values = $this->registry->values();
        $canRescan = current_user_can('manage_options');
        include FLASHSITE_CORE_PATH . 'templates/admin/site-texts.php';
    }

    /**
     * Campos agrupados por página, na ordem do site: página inicial, outras páginas (ordem do
     * menu de páginas), páginas modelo e, por fim, o que aparece em todas as páginas. Dentro de
     * cada página, as partes e os campos seguem a ordem em que aparecem na página.
     *
     * @return list<array{title: string, anchor: string, url: string, sections: array<string, list<array<string, mixed>>>}>
     */
    public function groupedFields(string $language = ''): array
    {
        $groups = [];
        foreach ($this->registry->fieldsFor($language) as $field) {
            $pageId = (int) (($field['pages'] ?? [])[0] ?? 0);
            if (! isset($groups[$pageId])) {
                $isPage = $pageId > 0 && get_post_type($pageId) === 'page';
                $groups[$pageId] = [
                    'title' => $pageId > 0 ? $this->pageLabel($pageId) : 'Outros',
                    'anchor' => 'fs-pagina-' . $pageId,
                    'url' => $isPage && ! str_starts_with((string) get_the_title($pageId), 'Modelo · ') ? (string) get_permalink($pageId) : '',
                    'order' => $this->pageOrder($pageId),
                    'sections' => [],
                ];
            }
            $section = (string) ($field['section'] ?? '');
            $field['show_url'] = $groups[$pageId]['url'] !== '' && (string) ($field['element'] ?? '') !== ''
                ? add_query_arg('fs_mostrar', rawurlencode((string) $field['element']), $groups[$pageId]['url'])
                : '';
            $groups[$pageId]['sections'][$section !== '' ? $section : 'Textos'][] = $field;
        }
        uasort($groups, static fn (array $a, array $b): int => $a['order'] <=> $b['order']);
        return array_values(array_map(static function (array $group): array {
            unset($group['order']);
            return $group;
        }, $groups));
    }

    /** @return array{int, int, int} */
    private function pageOrder(int $pageId): array
    {
        if ($this->isFront($pageId)) {
            return [0, 0, 0];
        }
        if ($pageId > 0 && get_post_type($pageId) === 'page') {
            $model = str_starts_with((string) get_the_title($pageId), 'Modelo · ') ? 2 : 1;
            return [$model, (int) get_post_field('menu_order', $pageId), $pageId];
        }
        return [3, 0, $pageId];
    }

    private function pageLabel(int $pageId): string
    {
        $title = wp_strip_all_tags((string) get_the_title($pageId));
        if ($this->isFront($pageId)) {
            return 'Página inicial';
        }
        if (get_post_type($pageId) !== 'page') {
            return 'Em todas as páginas';
        }
        // Páginas modelo das coleções ("Modelo · Imóveis topo"): mostra só a parte depois do prefixo.
        return str_starts_with($title, 'Modelo · ') ? substr($title, strlen('Modelo · ')) : $title;
    }

    /** Página inicial, também nas traduções (filtro flashsite/front_page_ids, do módulo Multilingual). */
    private function isFront(int $pageId): bool
    {
        if ($pageId <= 0) {
            return false;
        }
        $ids = (array) apply_filters('flashsite/front_page_ids', [(int) get_option('page_on_front')]);
        return in_array($pageId, array_map('intval', $ids), true);
    }

    /**
     * "Mostrar no site": a página abre em ?fs_mostrar=<id do elemento> e o texto fica marcado.
     * Só para quem pode editar os textos (sessão iniciada, sem cache).
     */
    public function printHighlightScript(): void
    {
        if (empty($_GET['fs_mostrar']) || ! current_user_can(self::CAP)) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }
        $id = preg_replace('/[^a-zA-Z0-9]/', '', (string) wp_unslash($_GET['fs_mostrar'])); // phpcs:ignore WordPress.Security
        if ($id === '') {
            return;
        }
        ?>
<style>.fs-mostrar{outline:3px solid #E8A33D!important;outline-offset:6px;border-radius:4px;animation:fs-mostrar 1.2s ease-in-out 3}@keyframes fs-mostrar{50%{outline-color:transparent}}</style>
<script>
(function () {
    var el = document.querySelector('[data-id="<?php echo esc_js($id); ?>"]');
    if (!el) { return; }
    el.classList.add('fs-mostrar');
    setTimeout(function () { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 300);
})();
</script>
        <?php
    }

    public function handleSave(): void
    {
        if (! current_user_can(self::CAP)) {
            wp_die(esc_html__('Sem permissão.', 'flashsite-core'));
        }
        check_admin_referer('flashsite_site_texts_save');
        $input = isset($_POST['fs_text']) && is_array($_POST['fs_text']) ? wp_unslash($_POST['fs_text']) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $changed = $this->registry->saveValues($input);
        $this->purge($changed);
        $over = array_values(array_intersect($changed, $this->registry->overLimit()));
        $language = isset($_POST['fs_idioma']) ? sanitize_text_field((string) wp_unslash($_POST['fs_idioma'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $args = ['page' => self::SLUG, 'saved' => count($changed), 'over' => implode(',', $over)];
        if (Language::isSupported($language)) {
            $args['idioma'] = $language;
        }
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public function handleRescan(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Sem permissão.', 'flashsite-core'));
        }
        check_admin_referer('flashsite_site_texts_rescan');
        $count = $this->rescanAll();
        wp_safe_redirect(add_query_arg(['page' => self::SLUG, 'scanned' => $count], admin_url('admin.php')));
        exit;
    }

    /**
     * Limpa o cache das páginas onde os textos mudados aparecem. Texto de cabeçalho/rodapé (em
     * todo o site): limpa página a página, nunca o cache inteiro.
     *
     * @param list<string> $keys
     */
    private function purge(array $keys): void
    {
        if ($keys === []) {
            return;
        }
        $fields = $this->registry->fields();
        $targets = [];
        $siteWide = false;
        foreach ($keys as $key) {
            foreach ((array) ($fields[$key]['pages'] ?? []) as $pageId) {
                $pageId = (int) $pageId;
                $title = (string) get_the_title($pageId);
                if (get_post_type($pageId) !== 'page' || str_starts_with($title, 'Modelo · ')) {
                    $siteWide = true;
                } else {
                    $targets[] = $pageId;
                }
            }
        }
        if ($siteWide) {
            $targets = array_merge($targets, get_posts(['post_type' => 'any', 'post_status' => 'publish', 'posts_per_page' => 500, 'fields' => 'ids', 'suppress_filters' => true]));
        }
        foreach (array_unique(array_map('intval', $targets)) as $id) {
            do_action('litespeed_purge_post', $id);
            clean_post_cache($id);
        }
        do_action('flashsite/site_texts/changed', $keys);
    }
}
