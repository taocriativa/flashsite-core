<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * JSON-LD por item, a partir do tipo schema.org do preset.
 *
 * Settings do preset:
 *   schema_availability ['taxonomy' => 'estado', 'map' => ['vendido' => 'SoldOut', ...]]
 *
 * @since 2.6.0
 */
final class SchemaOrgBuilder
{
    public function __construct(private CollectionPublicSerializer $serializer) {}

    /** @return array<string, mixed>|null */
    public function build(CollectionPresetInterface $preset, \WP_Post $post): ?array
    {
        $type = $preset->schemaType();
        if ($type === null) {
            return null;
        }
        $item = $this->serializer->item($preset, $post);

        $images = [];
        if (is_array($item['cover'])) {
            $images[] = $item['cover']['url'];
        }
        foreach ($item['fields'] as $value) {
            if (is_array($value) && isset($value[0]['url'])) {
                foreach ($value as $image) {
                    $images[] = $image['url'];
                }
            }
        }
        $images = array_values(array_unique(array_filter($images)));

        $data = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $item['title'],
            'url' => $item['link'],
        ];
        if ($item['excerpt'] !== '') {
            $data['description'] = $item['excerpt'];
        }
        if ($images !== []) {
            $data['image'] = $images;
        }
        if ($type === 'RealEstateListing') {
            $data['datePosted'] = $item['date'];
        }

        $priceKey = (string) (PriceBandResolver::config($preset)['field'] ?? 'preco');
        $price = $item['fields'][$priceKey] ?? null;
        if (is_float($price) || is_int($price)) {
            $offer = [
                '@type' => 'Offer',
                'price' => number_format((float) $price, 2, '.', ''),
                'priceCurrency' => 'EUR',
            ];
            $availability = $this->availability($preset, $item['terms']);
            if ($availability !== null) {
                $offer['availability'] = 'https://schema.org/' . $availability;
            }
            $data['offers'] = $offer;
        }

        return $data;
    }

    /** @param array<string, list<array{slug: string, name: string}>> $terms */
    private function availability(CollectionPresetInterface $preset, array $terms): ?string
    {
        $config = $preset->setting('schema_availability');
        if (! is_array($config) || ! isset($config['taxonomy'], $config['map'])) {
            return null;
        }
        foreach ($terms[(string) $config['taxonomy']] ?? [] as $term) {
            if (isset($config['map'][$term['slug']])) {
                return (string) $config['map'][$term['slug']];
            }
        }
        return null;
    }
}
