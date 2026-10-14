<?php
declare(strict_types=1);

// Stub mínimo do Elementor para GroupedArchiveContextTest.
namespace Elementor;

if (! class_exists(Plugin::class, false)) {
    final class FsTestFrontend
    {
        /** O "cartão": devolve o item que a Dynamic Tag resolve naquele momento. */
        public function get_builder_content_for_display(int $pageId, bool $withCss = false): string
        {
            $ctx = &$GLOBALS['fs_ctx'];
            $before = $ctx['global'];
            if ($ctx['elementor'] === 'switch') {
                // Comportamento clássico do Elementor: o post global passa a ser a página modelo.
                $ctx['global'] = $pageId;
            }
            $id = \FsGroupedArchiveTestTag::resolve();
            $ctx['global'] = $before;
            return 'card:' . $id . ';';
        }
    }

    final class Plugin
    {
        public static ?Plugin $instance = null;
        public FsTestFrontend $frontend;
        public static function instance(): Plugin { return self::$instance ??= new self(); }
        public function __construct() { $this->frontend = new FsTestFrontend(); }
    }
}
