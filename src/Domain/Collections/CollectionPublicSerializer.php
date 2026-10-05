<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Representação pública de um item (REST pública, JSON-LD, export de demonstração).
 * Só campos com public=true. Campos privados (_fs_*) nunca entram.
 *
 * @since 2.6.0
 */
final class CollectionPublicSerializer
{
    public function __construct(private ItemReader $reader, private FieldFormatter $formatter) {}

    /** @return array<string, mixed> */
    public function item(CollectionPresetInterface $preset, \WP_Post $post): array
    {
        $fields = $this->reader->read($preset, $post->ID, false);
        $formatted = [];
        foreach ($preset->publicFields() as $field) {
            $formatted[$field->key] = $this->formatter->field($preset, $field, $post->ID);
        }

        // Preço sob consulta: o valor numérico não sai.
        if ($this->formatter->isOnRequest($preset, $post->ID)) {
            $priceKey = (string) (PriceBandResolver::config($preset)['field'] ?? '');
            if ($priceKey !== '' && array_key_exists($priceKey, $fields)) {
                $fields[$priceKey] = null;
            }
        }

        foreach ($preset->publicFields() as $field) {
            if ($field->type === FieldType::Gallery) {
                $fields[$field->key] = array_values(array_filter(array_map(
                    static fn (int $id): ?array => ($url = wp_get_attachment_image_url($id, 'large')) ? ['id' => $id, 'url' => (string) $url] : null,
                    (array) $fields[$field->key]
                )));
            } elseif ($field->type === FieldType::Image || $field->type === FieldType::File) {
                $id = (int) $fields[$field->key];
                $fields[$field->key] = $id > 0 ? ['id' => $id, 'url' => (string) wp_get_attachment_url($id)] : null;
            }
        }

        $terms = [];
        foreach ($preset->taxonomies() as $taxonomy) {
            $list = wp_get_object_terms($post->ID, $taxonomy->taxonomyName($preset->postType()));
            $terms[$taxonomy->key] = is_array($list)
                ? array_values(array_map(static fn ($t) => ['slug' => (string) $t->slug, 'name' => (string) $t->name], $list))
                : [];
        }

        $thumbnailId = (int) get_post_thumbnail_id($post->ID);

        return [
            'id' => $post->ID,
            'type' => $preset->key(),
            'title' => get_the_title($post),
            'slug' => $post->post_name,
            'link' => (string) get_permalink($post),
            'date' => (string) get_post_time('c', true, $post),
            'excerpt' => wp_strip_all_tags((string) get_the_excerpt($post)),
            'cover' => $thumbnailId > 0 ? ['id' => $thumbnailId, 'url' => (string) wp_get_attachment_image_url($thumbnailId, 'large')] : null,
            'fields' => $fields,
            'formatted' => $formatted,
            'terms' => $terms,
        ];
    }
}
