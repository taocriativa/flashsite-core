<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\SiteTexts;

use FlashSite\Core\Core\Contracts\ModuleInterface;
use FlashSite\Core\Domain\SiteTexts\SiteTextsRegistry;

/**
 * FlashSite › Textos do site (@since 3.1.0): o cliente muda os textos marcados com a tag
 * "Texto do site" sem abrir o Elementor. A lista de campos é lida das páginas do Elementor
 * quando são salvas, quando o Core muda de versão e pelo botão "Atualizar lista".
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
        $this->registry->syncPage($postId, $found);
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
        $groups = $this->groupedFields();
        $values = $this->registry->values();
        $canRescan = current_user_can('manage_options');
        include FLASHSITE_CORE_PATH . 'templates/admin/site-texts.php';
    }

    /**
     * Campos agrupados por página (título) e parte da página, pela ordem em que aparecem.
     *
     * @return array<string, array{url: string, sections: array<string, list<array<string, mixed>>>}>
     */
    public function groupedFields(): array
    {
        $groups = [];
        foreach ($this->registry->fields() as $field) {
            $pageId = (int) (($field['pages'] ?? [])[0] ?? 0);
            $title = $pageId > 0 ? $this->pageLabel($pageId) : 'Outros';
            $groups[$title]['url'] ??= $pageId > 0 && get_post_type($pageId) === 'page' ? (string) get_permalink($pageId) : '';
            $section = (string) ($field['section'] ?? '');
            $groups[$title]['sections'][$section !== '' ? $section : 'Textos'][] = $field;
        }
        return $groups;
    }

    private function pageLabel(int $pageId): string
    {
        $title = wp_strip_all_tags((string) get_the_title($pageId));
        if ((int) get_option('page_on_front') === $pageId) {
            return 'Página inicial';
        }
        if (get_post_type($pageId) !== 'page') {
            return 'Em todas as páginas';
        }
        // Páginas modelo das coleções ("Modelo · Imóveis topo"): mostra só a parte depois do prefixo.
        return str_starts_with($title, 'Modelo · ') ? substr($title, strlen('Modelo · ')) : $title;
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
        wp_safe_redirect(add_query_arg(['page' => self::SLUG, 'saved' => count($changed)], admin_url('admin.php')));
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
