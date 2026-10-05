<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Admin;

use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\FieldType;
use FlashSite\Core\Domain\Collections\ItemReader;

/**
 * Colunas da listagem de itens no painel: capa + campos com admin_column.
 *
 * @since 2.6.0
 */
final class ListColumns
{
    /** @var array<string, CollectionPresetInterface> */
    private array $presets = [];

    public function __construct(private ItemReader $reader) {}

    /** @param array<string, CollectionPresetInterface> $presets */
    public function register(array $presets): void
    {
        foreach ($presets as $preset) {
            $postType = $preset->postType();
            $this->presets[$postType] = $preset;
            add_filter('manage_' . $postType . '_posts_columns', fn (array $columns): array => $this->columns($preset, $columns));
            add_action('manage_' . $postType . '_posts_custom_column', fn (string $column, int $postId) => $this->render($preset, $column, $postId), 10, 2);
        }
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function columns(CollectionPresetInterface $preset, array $columns): array
    {
        $result = [];
        foreach ($columns as $key => $label) {
            if ($key === 'title') {
                $result['fs_cover'] = '<span class="dashicons dashicons-format-image" title="Capa"></span>';
            }
            $result[$key] = $label;
            if ($key === 'title') {
                foreach ($preset->fields() as $field) {
                    if ($field->adminColumn) {
                        $result['fs_' . $field->key] = esc_html($field->columnLabel);
                    }
                }
            }
        }
        return $result;
    }

    public function render(CollectionPresetInterface $preset, string $column, int $postId): void
    {
        if ($column === 'fs_cover') {
            $thumb = get_the_post_thumbnail($postId, [48, 48]);
            echo $thumb !== '' ? $thumb : '<span class="dashicons dashicons-camera" style="color:#c3c4c7" title="Sem foto"></span>';
            return;
        }
        if (! str_starts_with($column, 'fs_')) {
            return;
        }
        $field = $preset->field(substr($column, 3));
        if ($field === null) {
            return;
        }
        $value = $this->reader->value($field, $postId);

        echo match ($field->type) {
            FieldType::Bool => $value === true ? '<span class="dashicons dashicons-star-filled" style="color:#dba617" title="Sim"></span>' : '—',
            FieldType::Money => $this->money($preset, $postId, $value),
            FieldType::Number => $value === null ? '—' : esc_html(rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',') . ($field->unit !== '' ? ' ' . $field->unit : '')),
            default => is_scalar($value) && (string) $value !== '' ? esc_html((string) $value) : '—',
        };
    }

    private function money(CollectionPresetInterface $preset, int $postId, mixed $value): string
    {
        $flagKey = $preset->setting('price_bands')['flag'] ?? null;
        $flag = is_string($flagKey) ? $preset->field($flagKey) : null;
        if ($flag !== null && $this->reader->value($flag, $postId) === true) {
            return 'Sob consulta';
        }
        if ($value === null) {
            return '—';
        }
        return esc_html(number_format((float) $value, 2, ',', '.') . ' €');
    }
}
