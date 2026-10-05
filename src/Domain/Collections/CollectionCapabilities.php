<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

/**
 * Permissões das Coleções.
 *
 * - Gestor de Site e Administrador criam, editam, publicam e apagam itens dos presets ativos.
 * - Só o Administrador tem `flashsite_manage_collections` (ligar/desligar presets, termos fixos).
 * - O Gestor de Site recebe `upload_files` para carregar fotos (decisão de 05/10/2026).
 *
 * A sincronização só escreve nos papéis quando o conjunto ativo muda (hash guardado).
 *
 * @since 2.6.0
 */
final class CollectionCapabilities
{
    public const MANAGE_CAP = 'flashsite_manage_collections';
    public const SITE_MANAGER_ROLE = 'flashsite_site_manager';
    public const HASH_OPTION = 'flashsite_collections_caps_hash';
    private const VERSION = '1';

    public function __construct(private OptionsStorage $storage) {}

    /** @return list<string> capabilities primitivas de um preset (map_meta_cap). */
    public static function itemCaps(CollectionPresetInterface $preset): array
    {
        $p = $preset->capabilityPlural();
        return [
            "edit_{$p}",
            "edit_others_{$p}",
            "edit_private_{$p}",
            "edit_published_{$p}",
            "publish_{$p}",
            "read_private_{$p}",
            "delete_{$p}",
            "delete_others_{$p}",
            "delete_private_{$p}",
            "delete_published_{$p}",
        ];
    }

    /** @return array<string, string> capabilities de taxonomia para register_taxonomy(). */
    public static function taxonomyCaps(CollectionPresetInterface $preset, TaxonomyDefinition $taxonomy): array
    {
        $edit = 'edit_' . $preset->capabilityPlural();
        $structure = $taxonomy->locked ? self::MANAGE_CAP : $edit;
        return [
            'manage_terms' => $structure,
            'edit_terms' => $structure,
            'delete_terms' => $structure,
            'assign_terms' => $edit,
        ];
    }

    /**
     * @param list<string> $activeKeys
     */
    public function sync(CollectionRegistry $registry, array $activeKeys, bool $force = false): bool
    {
        $hash = md5(self::VERSION . '|' . implode(',', $registry->keys()) . '|' . implode(',', $activeKeys));
        if (! $force && $this->storage->get(self::HASH_OPTION, '') === $hash) {
            return false;
        }

        $roles = array_filter([
            'administrator' => get_role('administrator'),
            self::SITE_MANAGER_ROLE => get_role(self::SITE_MANAGER_ROLE),
        ]);

        foreach ($registry->all() as $key => $preset) {
            $active = in_array($key, $activeKeys, true);
            foreach (self::itemCaps($preset) as $cap) {
                foreach ($roles as $role) {
                    $active ? $role->add_cap($cap) : $role->remove_cap($cap);
                }
            }
        }

        if (isset($roles['administrator'])) {
            $roles['administrator']->add_cap(self::MANAGE_CAP);
        }
        if (isset($roles[self::SITE_MANAGER_ROLE])) {
            $roles[self::SITE_MANAGER_ROLE]->add_cap('upload_files');
            // Garantia: o gestor nunca altera a estrutura.
            $roles[self::SITE_MANAGER_ROLE]->remove_cap(self::MANAGE_CAP);
        }

        $this->storage->update(self::HASH_OPTION, $hash, true);
        return true;
    }
}
