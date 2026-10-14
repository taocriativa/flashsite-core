<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Modules\Collections\Output\Elementor\CollectionTagBase;
use FlashSite\Core\Modules\Collections\Output\ModelPages;

/*
 * Stubs mínimos do loop do WordPress e do Elementor para este teste.
 * Estado em $GLOBALS['fs_ctx']: posts (id => tipo), fila do loop, post global e modo do Elementor.
 */
if (! function_exists('have_posts')) {
    function have_posts(): bool { return $GLOBALS['fs_ctx']['cursor'] < count($GLOBALS['fs_ctx']['queue']); }
    function the_post(): void { $GLOBALS['fs_ctx']['global'] = $GLOBALS['fs_ctx']['queue'][$GLOBALS['fs_ctx']['cursor']++]; }
    function get_the_ID(): int { return (int) $GLOBALS['fs_ctx']['global']; }
    function get_post_type(int|object|null $post = null): string|false
    {
        $id = is_object($post) ? (int) $post->ID : (int) $post;
        return $GLOBALS['fs_ctx']['types'][$id] ?? false;
    }
    function get_post(int|object|null $post = null): ?object { $id = is_object($post) ? (int) $post->ID : (int) $post; return isset($GLOBALS['fs_ctx']['types'][$id]) ? (object) ['ID' => $id] : null; }
    function setup_postdata(object|int $post): bool { $GLOBALS['fs_ctx']['global'] = is_object($post) ? (int) $post->ID : (int) $post; return true; }
    function wp_reset_postdata(): void { $GLOBALS['fs_ctx']['global'] = $GLOBALS['fs_ctx']['main'] ?? 0; }
}

require_once dirname(__DIR__) . '/fixtures/elementor_frontend_stub.php';

/** Tag real do Core (CollectionTagBase) com o mínimo para chamar currentPostId(). */
final class FsGroupedArchiveTestTag extends CollectionTagBase
{
    protected function slug(): string { return 'fs-test'; }
    protected function title(): string { return 'Teste'; }
    public function get_settings($key = null) { return null; }
    public static function resolve(): int { return (new self())->currentPostId(); }
}

/**
 * Arquivo agrupado (settings.archive_group_by, ex.: Menu) com cartões da página modelo:
 * cada cartão tem de mostrar o seu prato, e não o último item do loop.
 *
 * Reproduz a regressão vista no Demo Restaurante (Core 3.4.0-beta.1 + Elementor 4.3.5): os 9 cartões
 * mostravam o mesmo prato, embora a query e a REST devolvessem os 9 corretos.
 */
final class GroupedArchiveContextTest extends TestCase
{
    public function run(): void
    {
        foreach (['no_switch', 'switch'] as $mode) {
            $this->assertEveryCardShowsItsOwnItem($mode);
        }
    }

    private function assertEveryCardShowsItsOwnItem(string $elementorMode): void
    {
        flashsite_reset_test_state();
        $GLOBALS['flashsite_test_terms_as_objects'] = true;
        $registry = CollectionRegistry::fromDirectory(dirname(__DIR__, 2) . '/config/collections');
        $prato = $registry->get('prato');
        $taxonomy = $prato->taxonomies()[0]->taxonomyName($prato->postType());

        // 9 pratos em 3 categorias; o loop principal termina no último (como o ID 50 do demo).
        $categories = ['entradas' => [11, 12, 13], 'pratos' => [21, 22, 23], 'sobremesas' => [31, 32, 50]];
        $types = [900 => 'page'];
        $queue = [];
        foreach ($categories as $slug => $ids) {
            $termId = wp_insert_term(ucfirst($slug), $taxonomy, ['slug' => $slug])['term_id'];
            foreach ($ids as $id) {
                $types[$id] = $prato->postType();
                wp_set_object_terms($id, [$termId], $taxonomy);
                $queue[] = $id;
            }
        }
        $GLOBALS['fs_ctx'] = ['types' => $types, 'queue' => $queue, 'cursor' => 0, 'global' => 0, 'main' => 11, 'elementor' => $elementorMode];

        $modelPages = new ModelPages();
        $method = new ReflectionMethod($modelPages, 'renderGrouped');
        $method->setAccessible(true);
        ob_start();
        $method->invoke($modelPages, $prato, $prato->taxonomies()[0]->key, 900);
        $html = (string) ob_get_clean();

        preg_match_all('/card:(\d+);/', $html, $matches);
        $this->assertSame(
            [11, 12, 13, 21, 22, 23, 31, 32, 50],
            array_map('intval', $matches[1]),
            "Elementor '$elementorMode': cada cartão tem de mostrar o seu item, na ordem das categorias."
        );
        $GLOBALS['flashsite_test_terms_as_objects'] = false;
        unset($GLOBALS['fs_ctx']);
    }
}
