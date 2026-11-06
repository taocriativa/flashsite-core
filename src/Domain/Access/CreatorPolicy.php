<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Access;

/**
 * Regras do modo Criador (@since 3.7.0), sem WordPress: tudo o que aqui está é testável isolado.
 *
 * O Criador é quem a constante FLASHSITE_CRIADOR (wp-config.php) nomeia, por login ou e-mail.
 * Os outros utilizadores, administradores incluídos, ficam com um painel limpo.
 */
final class CreatorPolicy
{
    /** Plugins técnicos: somem da lista de plugins, dos menus, dos avisos, do Painel e da barra. */
    public const HIDDEN_PLUGINS = ['full-customer', 'litespeed-cache', 'code-snippets', 'wps-hide-login', 'simple-history', 'hostinger*'];

    /** Plugins de base: somem só da lista de plugins (não se desativam por engano); os menus ficam. */
    public const PROTECTED_PLUGINS = ['flashsite-*', '*-kit', 'elementor', 'elementor-pro'];

    /** Capacidades que só o Criador tem: instalar, editar, atualizar e trocar código do site. */
    public const BLOCKED_CAPS = [
        'install_plugins', 'upload_plugins', 'edit_plugins', 'delete_plugins', 'update_plugins',
        'install_themes', 'upload_themes', 'edit_themes', 'delete_themes', 'update_themes', 'switch_themes',
        'update_core', 'edit_files', 'install_languages', 'update_languages',
    ];

    /** Ações sobre um utilizador que ninguém além do Criador faz contra o Criador. */
    public const USER_CAPS = ['edit_user', 'delete_user', 'remove_user', 'promote_user'];

    /** Ações sobre um plugin concreto (o 1.º argumento é o arquivo do plugin). */
    public const PLUGIN_CAPS = ['activate_plugin', 'deactivate_plugin'];

    /** "ted, ola@flashsite.pt ; outro" → ['ted', 'ola@flashsite.pt', 'outro'] (minúsculas, sem repetidos). */
    public static function parseList(string $raw): array
    {
        $items = preg_split('/[\s,;]+/', strtolower(trim($raw))) ?: [];
        return array_values(array_unique(array_filter($items, static fn (string $item): bool => $item !== '')));
    }

    public static function matchesUser(string $login, string $email, array $creators): bool
    {
        if ($creators === []) {
            return false;
        }
        $login = strtolower(trim($login));
        $email = strtolower(trim($email));
        return ($login !== '' && in_array($login, $creators, true)) || ($email !== '' && in_array($email, $creators, true));
    }

    /** Pasta do plugin ("full-customer") a partir do arquivo ("full-customer/full-customer.php" ou "hello.php"). */
    public static function slugFromBasename(string $basename): string
    {
        $basename = ltrim(str_replace('\\', '/', $basename), '/');
        $slash = strpos($basename, '/');
        return $slash === false ? preg_replace('/\.php$/', '', $basename) : substr($basename, 0, $slash);
    }

    /** Pasta do plugin dono de um arquivo PHP, ou '' se o arquivo não estiver em $pluginsDir. */
    public static function slugFromFile(string $file, string $pluginsDir): string
    {
        $file = str_replace('\\', '/', $file);
        $dir = rtrim(str_replace('\\', '/', $pluginsDir), '/') . '/';
        if ($dir === '/' || ! str_starts_with($file, $dir)) {
            return '';
        }
        return self::slugFromBasename(substr($file, strlen($dir)));
    }

    /** Padrões com * ("hostinger*", "*-kit"). */
    public static function slugMatches(string $slug, array $patterns): bool
    {
        if ($slug === '') {
            return false;
        }
        foreach ($patterns as $pattern) {
            if (fnmatch(strtolower((string) $pattern), strtolower($slug))) {
                return true;
            }
        }
        return false;
    }

    /** Lista de plugins do WordPress (basename => dados) sem os ocultos e os protegidos. */
    public static function filterPluginList(array $plugins, array $hidden, array $protected): array
    {
        $patterns = array_merge($hidden, $protected);
        return array_filter(
            $plugins,
            static fn (string $basename): bool => ! self::slugMatches(self::slugFromBasename($basename), $patterns),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Decide uma capacidade para quem não é Criador.
     *
     * @param list<int> $creatorIds
     * @return bool true = negar (do_not_allow)
     */
    public static function shouldDeny(string $cap, array $args, array $creatorIds, array $blockedCaps, array $hidden, array $protected): bool
    {
        if (in_array($cap, $blockedCaps, true)) {
            return true;
        }
        if (in_array($cap, self::USER_CAPS, true)) {
            return isset($args[0]) && in_array((int) $args[0], $creatorIds, true);
        }
        if (in_array($cap, self::PLUGIN_CAPS, true) && isset($args[0]) && is_string($args[0])) {
            return self::slugMatches(self::slugFromBasename($args[0]), array_merge($hidden, $protected));
        }
        return false;
    }
}
