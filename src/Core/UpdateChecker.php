<?php
declare(strict_types=1);

namespace FlashSite\Core\Core;

final class UpdateChecker
{
    private const ENDPOINT     = 'https://raw.githubusercontent.com/taocriativa/flashsite-core/main/update-server/flashsite-core.json';
    private const TRANSIENT    = 'flashsite_core_update_data';
    private const CHECK_EVERY  = 43200; // 12 h

    public function __construct(
        private readonly string $pluginFile,
        private readonly string $pluginSlug,
        private readonly string $currentVersion
    ) {}

    public function register(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'injectUpdateInfo']);
        add_filter('plugins_api', [$this, 'injectPluginInfo'], 20, 3);
        add_action('delete_site_transient_update_plugins', [$this, 'clearCache']);
    }

    public function clearCache(): void
    {
        delete_transient(self::TRANSIENT);
    }

    /** @param mixed $transient */
    public function injectUpdateInfo(mixed $transient): mixed
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = $this->fetchRemoteData();

        if ($remote === null) {
            return $transient;
        }

        if (version_compare($this->currentVersion, $remote['version'], '<')) {
            $item               = new \stdClass();
            $item->id           = $this->pluginSlug;
            $item->slug         = $this->pluginSlug;
            $item->plugin       = $this->pluginFile;
            $item->new_version  = $remote['version'];
            $item->tested       = $remote['tested']        ?? '';
            $item->requires     = $remote['requires']      ?? '';
            $item->requires_php = $remote['requires_php']  ?? '';
            $item->package      = $remote['download_url'];
            $item->url          = $remote['changelog']     ?? '';
            $item->icons        = [];
            $item->banners      = [];
            $transient->response[$this->pluginFile] = $item;
        } else {
            $noUpdate              = new \stdClass();
            $noUpdate->id          = $this->pluginSlug;
            $noUpdate->slug        = $this->pluginSlug;
            $noUpdate->plugin      = $this->pluginFile;
            $noUpdate->new_version = $remote['version'];
            $noUpdate->url         = $remote['changelog'] ?? '';
            $noUpdate->package     = '';
            $transient->no_update[$this->pluginFile] = $noUpdate;
        }

        return $transient;
    }

    /** @param mixed $result @param mixed $args */
    public function injectPluginInfo(mixed $result, string $action, mixed $args): mixed
    {
        if ($action !== 'plugin_information' || ($args->slug ?? '') !== $this->pluginSlug) {
            return $result;
        }

        $remote = $this->fetchRemoteData();
        if ($remote === null) {
            return $result;
        }

        $changelogUrl  = $remote['changelog'] ?? '';
        $changelogHtml = $changelogUrl
            ? '<p><a href="' . esc_url($changelogUrl) . '" target="_blank">Ver changelog completo</a></p>'
            : '';

        $info                = new \stdClass();
        $info->name          = 'FlashSite Core';
        $info->slug          = $this->pluginSlug;
        $info->version       = $remote['version'];
        $info->author        = 'FlashSite';
        $info->requires      = $remote['requires']     ?? '';
        $info->tested        = $remote['tested']       ?? '';
        $info->requires_php  = $remote['requires_php'] ?? '';
        $info->download_link = $remote['download_url'];
        $info->sections      = ['changelog' => $changelogHtml];

        return $info;
    }

    private function fetchRemoteData(): ?array
    {
        $cached = get_transient(self::TRANSIENT);

        if (is_array($cached)) {
            return isset($cached['_error']) ? null : $cached;
        }

        $response = wp_remote_get(self::ENDPOINT, [
            'timeout'    => 8,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; FlashSite-Core/' . $this->currentVersion,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            set_transient(self::TRANSIENT, ['_error' => true], self::CHECK_EVERY);
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($data) || empty($data['version']) || empty($data['download_url'])) {
            set_transient(self::TRANSIENT, ['_error' => true], self::CHECK_EVERY);
            return null;
        }

        set_transient(self::TRANSIENT, $data, self::CHECK_EVERY);
        return $data;
    }
}
