<?php
declare(strict_types=1);

use FlashSite\Core\Domain\Collections\CollectionPublicSerializer;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\ItemReader;
use FlashSite\Core\Domain\Collections\SchemaOrgBuilder;

if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public string $post_name = 'item-de-teste';
        public string $post_status = 'publish';
        // Campos usados também pelos testes das Abilities (tests/Support/WpPostStubs.php).
        public string $post_type = 'post';
        public string $post_title = '';
        public string $post_content = '';
        public string $post_modified_gmt = '2026-10-14 10:00:00';
        public int $post_author = 1;
        public function __construct(public int $ID = 0) {}
    }
}
foreach ([
    'get_the_title' => static fn ($post = 0): string => 'Título ' . (is_object($post) ? $post->ID : $post),
    'get_permalink' => static fn ($post = 0): string => 'https://exemplo.test/item/' . (is_object($post) ? $post->ID : $post) . '/',
    'get_post_time' => static fn ($format = 'U', $gmt = false, $post = null): string => '2026-10-01T10:00:00+00:00',
    'get_the_excerpt' => static fn ($post = 0): string => 'Resumo de teste.',
    'wp_strip_all_tags' => static fn ($text, $removeBreaks = false): string => trim(strip_tags((string) $text)),
    'get_post_thumbnail_id' => static fn ($post = 0): int => 0,
] as $name => $impl) {
    if (! function_exists($name)) {
        eval('function ' . $name . '(...$args) { return ($GLOBALS["fs_jsonld_stubs"]["' . $name . '"])(...$args); }');
    }
    $GLOBALS['fs_jsonld_stubs'][$name] = $impl;
}

/** Monta o JSON-LD de um item de teste com o SchemaOrgBuilder real (sem WordPress). */
final class JsonLdHarness
{
    /**
     * @param array<string, mixed> $meta meta key => valor (ex.: 'fs_preco' => 285000.0)
     * @param array<string, list<string>> $terms chave da taxonomia => slugs
     * @return array<string, mixed>|null
     */
    public static function build(string $configDir, string $key, string $market, int $postId, array $meta, array $terms): ?array
    {
        $GLOBALS['flashsite_test_terms_as_objects'] = true;
        $preset = CollectionRegistry::fromDirectory($configDir, $market)->get($key);
        foreach ($meta as $metaKey => $value) {
            $GLOBALS['flashsite_test_post_meta'][$postId][$metaKey] = $value;
        }
        foreach ($terms as $taxKey => $slugs) {
            $taxonomy = $preset->postType() . '_' . $taxKey;
            $ids = [];
            foreach ($slugs as $slug) {
                $ids[] = wp_insert_term(ucfirst($slug), $taxonomy, ['slug' => $slug])['term_id'];
            }
            wp_set_object_terms($postId, $ids, $taxonomy);
        }
        $reader = new ItemReader();
        $formatter = new FieldFormatter($reader);
        $builder = new SchemaOrgBuilder(new CollectionPublicSerializer($reader, $formatter));
        $data = $builder->build($preset, new WP_Post($postId));
        $GLOBALS['flashsite_test_terms_as_objects'] = false;
        return $data;
    }

    /** Casos dos presets antigos usados na prova de que o JSON-LD não mudou. @return array<string, array<string, mixed>|null> */
    public static function legacyCases(string $configDir): array
    {
        $out = [];
        flashsite_reset_test_state();
        $out['imovel:PT'] = self::build($configDir, 'imovel', 'PT', 801, ['fs_preco' => 285000.0, 'fs_referencia' => 'AP1', 'fs_area_util' => 92.0], ['finalidade' => ['venda'], 'estado' => ['disponivel']]);
        $out['plano:PT'] = self::build($configDir, 'plano', 'PT', 802, ['fs_preco' => 149.0, 'fs_periodicidade' => 'mes', 'fs_resumo' => 'Plano de teste'], ['modalidade' => ['hibrido']]);
        $out['prato:PT'] = self::build($configDir, 'prato', 'PT', 803, ['fs_preco' => 16.5, 'fs_resumo' => 'Prato de teste'], ['categoria' => ['pratos']]);
        $out['plano:BR'] = self::build($configDir, 'plano', 'BR', 804, ['fs_preco' => 350.0], ['modalidade' => ['online']]);
        return $out;
    }
}
