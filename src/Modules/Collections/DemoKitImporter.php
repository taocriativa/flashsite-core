<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections;

use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\FieldType;

/**
 * Importa/remove o kit de itens fictícios de um preset (<pasta>/<key>.php).
 *
 * Os kits NÃO vêm no Core de produção: são fornecidos por um plugin à parte (FlashSite Demo Kit)
 * através do filtro "flashsite/collections/demo_kit_dirs". Sem esse plugin, a secção de exemplos
 * nem aparece no painel.
 *
 * - Idempotente: um item do kit com o mesmo título não é criado duas vezes.
 * - Itens marcados com _fs_demo_kit = 1; a remoção só toca nesses itens.
 * - Imagens referenciadas pelo slug do anexo; se faltar, o item fica sem essa foto.
 *
 * @since 2.6.0
 */
final class DemoKitImporter
{
    public const MARKER = '_fs_demo_kit';

    public function __construct(private ItemPersistence $persistence, private string $kitDirectory = '') {}

    public function hasKit(CollectionPresetInterface $preset): bool
    {
        $file = $this->kitFile($preset);
        return $file !== '' && is_file($file);
    }

    /** @return array{created: int, skipped: int, missing_images: list<string>} */
    public function import(CollectionPresetInterface $preset): array
    {
        $result = ['created' => 0, 'skipped' => 0, 'missing_images' => []];
        if (! $this->hasKit($preset)) {
            return $result;
        }
        $kit = require $this->kitFile($preset);
        $items = is_array($kit) && is_array($kit['items'] ?? null) ? $kit['items'] : [];

        $this->persistence->seedTerms($preset);

        foreach ($items as $index => $item) {
            $title = (string) ($item['title'] ?? '');
            if ($title === '' || $this->existingKitItem($preset, $title) > 0) {
                $result['skipped']++;
                continue;
            }

            $postId = wp_insert_post([
                'post_type' => $preset->postType(),
                'post_status' => 'publish',
                'post_title' => $title,
                'post_content' => (string) ($item['content'] ?? ''),
                'post_excerpt' => (string) ($item['excerpt'] ?? ''),
                'menu_order' => (int) $index,
            ], true);
            if (is_wp_error($postId) || (int) $postId <= 0) {
                $result['skipped']++;
                continue;
            }
            $postId = (int) $postId;
            update_post_meta($postId, self::MARKER, '1');

            $fields = is_array($item['fields'] ?? null) ? $item['fields'] : [];
            $imageIds = [];
            foreach ((array) ($item['images'] ?? []) as $slug) {
                $id = $this->attachmentIdBySlug((string) $slug);
                if ($id > 0) {
                    $imageIds[] = $id;
                } else {
                    $result['missing_images'][] = (string) $slug;
                }
            }
            foreach ($preset->fields() as $field) {
                if ($field->type === FieldType::Gallery && $imageIds !== []) {
                    $fields[$field->key] = $imageIds;
                    break;
                }
            }
            $this->persistence->saveFields($preset, $postId, $fields);

            $selected = [];
            foreach ((array) ($item['terms'] ?? []) as $taxKey => $value) {
                $ids = $this->termIds($preset, (string) $taxKey, $value);
                if ($ids !== []) {
                    $selected[(string) $taxKey] = $ids;
                }
            }
            $this->persistence->saveTerms($preset, $postId, $selected);
            $this->persistence->syncCover($preset, $postId);
            $result['created']++;
        }

        $result['missing_images'] = array_values(array_unique($result['missing_images']));
        return $result;
    }

    /** Move para o lixo os itens do kit (recuperáveis). Nunca toca em itens criados pelo cliente. */
    public function remove(CollectionPresetInterface $preset): int
    {
        $ids = get_posts([
            'post_type' => $preset->postType(),
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_key' => self::MARKER,
            'meta_value' => '1',
        ]);
        $count = 0;
        foreach ((array) $ids as $id) {
            if (wp_trash_post((int) $id)) {
                $count++;
            }
        }
        return $count;
    }

    private function kitFile(CollectionPresetInterface $preset): string
    {
        $dirs = (array) apply_filters('flashsite/collections/demo_kit_dirs', $this->kitDirectory !== '' ? [$this->kitDirectory] : []);
        foreach ($dirs as $dir) {
            $file = rtrim((string) $dir, '/\\') . DIRECTORY_SEPARATOR . $preset->key() . '.php';
            if ($dir !== '' && is_file($file)) {
                return $file;
            }
        }
        return '';
    }

    private function existingKitItem(CollectionPresetInterface $preset, string $title): int
    {
        $ids = get_posts([
            'post_type' => $preset->postType(),
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'title' => $title,
            'meta_key' => self::MARKER,
            'meta_value' => '1',
        ]);
        return (int) ($ids[0] ?? 0);
    }

    private function attachmentIdBySlug(string $slug): int
    {
        if ($slug === '') {
            return 0;
        }
        $ids = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'name' => sanitize_title($slug),
            'posts_per_page' => 1,
            'fields' => 'ids',
        ]);
        return (int) ($ids[0] ?? 0);
    }

    /**
     * Termos por slug; numa taxonomia hierárquica uma lista ["Lisboa", "Alvalade"] cria o caminho pai > filho.
     *
     * @return list<int>
     */
    private function termIds(CollectionPresetInterface $preset, string $taxKey, mixed $value): array
    {
        foreach ($preset->taxonomies() as $taxonomy) {
            if ($taxonomy->key !== $taxKey || $taxonomy->auto) {
                continue;
            }
            $name = $taxonomy->taxonomyName($preset->postType());
            if ($taxonomy->hierarchical && is_array($value)) {
                $ids = [];
                $parent = 0;
                foreach ($value as $termName) {
                    $parent = $this->persistence->ensureTerm($name, (string) $termName, sanitize_title((string) $termName), $parent);
                    if ($parent > 0) {
                        $ids[] = $parent;
                    }
                }
                return $ids;
            }
            $ids = [];
            foreach ((array) $value as $slug) {
                $label = $taxonomy->terms[(string) $slug] ?? (string) $slug;
                $id = $this->persistence->ensureTerm($name, $label, (string) $slug);
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
            return $ids;
        }
        return [];
    }
}
