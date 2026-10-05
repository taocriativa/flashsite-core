<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Output\Elementor;

use FlashSite\Core\Core\Plugin;
use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Domain\Collections\FieldType;
use FlashSite\Core\Domain\Collections\ItemReader;

require_once dirname(__DIR__, 3) . '/OutputFoundation/Elementor/ElementorTagBase.php';

/**
 * Base das Dynamic Tags das Coleções.
 *
 * Resolvem o item atual (página individual, Loop Grid, loop atómico) por get_the_ID()
 * e o preset pelo post type. Fora de um item da coleção devolvem vazio.
 *
 * @since 2.6.0
 */
abstract class CollectionTagBase extends \FlashSite\Core\Modules\OutputFoundation\Elementor\ElementorTagBase
{
    public const GROUP = 'flashsite-collections';

    abstract protected function slug(): string;
    abstract protected function title(): string;

    public function get_name(): string
    {
        return $this->slug();
    }

    public function get_title(): string
    {
        return $this->title();
    }

    public function get_group(): string
    {
        return self::GROUP;
    }

    protected function currentPostId(): int
    {
        $id = function_exists('get_the_ID') ? (int) get_the_ID() : 0;
        return $id > 0 ? $id : 0;
    }

    protected function currentPreset(int $postId): ?CollectionPresetInterface
    {
        $registry = self::service(CollectionRegistry::class);
        if (! $registry instanceof CollectionRegistry || $postId <= 0) {
            return null;
        }
        return $registry->byPostType((string) get_post_type($postId));
    }

    protected static function formatter(): ?FieldFormatter
    {
        $formatter = self::service(FieldFormatter::class);
        return $formatter instanceof FieldFormatter ? $formatter : null;
    }

    protected static function reader(): ?ItemReader
    {
        $reader = self::service(ItemReader::class);
        return $reader instanceof ItemReader ? $reader : null;
    }

    protected static function service(string $id): mixed
    {
        $application = Plugin::instance()->application();
        if ($application === null || ! $application->container()->has($id)) {
            return null;
        }
        return $application->container()->make($id);
    }

    /**
     * Opções "chave => label" de campos públicos de todos os presets, filtradas por tipo.
     *
     * @param list<FieldType> $types vazio = todos
     * @return array<string, string>
     */
    protected static function fieldOptions(array $types = []): array
    {
        $registry = self::service(CollectionRegistry::class);
        if (! $registry instanceof CollectionRegistry) {
            return [];
        }
        $options = [];
        foreach ($registry->all() as $preset) {
            foreach ($preset->publicFields() as $field) {
                if ($types !== [] && ! in_array($field->type, $types, true)) {
                    continue;
                }
                $options[$field->key] = isset($options[$field->key])
                    ? $options[$field->key]
                    : $preset->labels()['singular'] . ' · ' . $field->label;
            }
        }
        return $options;
    }

    /** @return array<string, string> */
    protected static function taxonomyOptions(): array
    {
        $registry = self::service(CollectionRegistry::class);
        if (! $registry instanceof CollectionRegistry) {
            return [];
        }
        $options = [];
        foreach ($registry->all() as $preset) {
            foreach ($preset->taxonomies() as $taxonomy) {
                $options[$taxonomy->key] ??= $preset->labels()['singular'] . ' · ' . $taxonomy->singularLabel;
            }
        }
        return $options;
    }
}
