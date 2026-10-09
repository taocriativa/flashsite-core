<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\ClientPanel;

/**
 * Fotos em uso protegidas (@since 3.1.0).
 *
 * O cliente não exclui da biblioteca um arquivo que o site usa: imagens das páginas e modelos do
 * Elementor (inclui logos e ícones), ícone do site, logos dos Dados do negócio e fotos de itens
 * das coleções (capa e galeria). Foto de imóvel troca-se ou tira-se na ficha do imóvel. Arquivos
 * sem uso continuam a poder ser excluídos.
 */
final class MediaGuard
{
    private const CACHE = 'flashsite_media_in_use';

    /** @var array<int, true>|null */
    private ?array $inUse = null;

    /**
     * IDs de anexos referidos num JSON do Elementor (atómico e clássico).
     *
     * @return list<int>
     */
    public static function extractIds(string $json): array
    {
        $ids = [];
        $patterns = [
            '/"image-attachment-id"\s*,\s*"value"\s*:\s*"?(\d+)/',   // atómico: {"$$type":"image-attachment-id","value":123}
            '/"id"\s*:\s*"?(\d+)"?\s*,\s*"url"\s*:/',               // clássico: {"id":123,"url":"…"}
            '/"url"\s*:\s*"[^"]*"\s*,\s*"id"\s*:\s*"?(\d+)/',        // clássico: {"url":"…","id":123}
        ];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $json, $m)) {
                foreach ($m[1] as $id) {
                    $ids[(int) $id] = true;
                }
            }
        }
        unset($ids[0]);
        return array_keys($ids);
    }

    /** @return array<int, true> */
    public function inUse(): array
    {
        if ($this->inUse !== null) {
            return $this->inUse;
        }
        $cached = get_transient(self::CACHE);
        if (is_array($cached)) {
            return $this->inUse = $cached;
        }
        global $wpdb;
        $ids = [];
        // Páginas, modelos, componentes e o kit do Elementor.
        $rows = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key IN ('_elementor_data', '_elementor_page_settings')"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        foreach ((array) $rows as $raw) {
            $json = is_string($raw) && str_starts_with(ltrim($raw), 'a:') ? (string) wp_json_encode(maybe_unserialize($raw)) : (string) $raw;
            foreach (self::extractIds($json) as $id) {
                $ids[$id] = true;
            }
        }
        // Capas e galerias de qualquer conteúdo.
        foreach ((array) $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id'") as $id) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $ids[(int) $id] = true;
        }
        foreach ((array) $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE 'fs\\_%' AND meta_value LIKE 'a:%'") as $raw) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $value = maybe_unserialize($raw);
            if (is_array($value)) {
                foreach ($value as $id) {
                    if (is_numeric($id)) {
                        $ids[(int) $id] = true;
                    }
                }
            }
        }
        // Ícone do site, logo do tema e logos dos Dados do negócio.
        $ids[(int) get_option('site_icon')] = true;
        $ids[(int) get_theme_mod('custom_logo')] = true;
        $profile = get_option('flashsite_business_profile', []);
        $branding = is_array($profile) && is_array($profile['branding'] ?? null) ? $profile['branding'] : [];
        foreach (['logo_light_id', 'logo_dark_id'] as $key) {
            $ids[(int) ($branding[$key] ?? 0)] = true;
        }
        unset($ids[0]);
        set_transient(self::CACHE, $ids, HOUR_IN_SECONDS);
        return $this->inUse = $ids;
    }

    public function isInUse(int $attachmentId): bool
    {
        return isset($this->inUse()[$attachmentId]);
    }

    public static function forget(): void
    {
        delete_transient(self::CACHE);
    }
}
