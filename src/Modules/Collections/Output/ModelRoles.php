<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Output;

use FlashSite\Core\Domain\Collections\CollectionPresetInterface;

/**
 * Papel de uma página modelo, escolhido nas Definições da página do Elementor (secção
 * "FlashSite · Página modelo") e guardado em _elementor_page_settings:
 *
 *   fs_model_role   ""                      página normal
 *                   "site:404"              página de erro 404
 *                   "site:header"           cabeçalho das páginas de texto
 *                   "site:footer"           rodapé das páginas de texto
 *                   "site:popup"            popup (vários permitidos, cada um com o seu gatilho)
 *                   "<preset>:item|card|archive_top|archive_bottom"
 *
 * O MCP do Elementor define estes valores com elementor-update-page-settings; o cliente
 * vê-os e muda-os no editor. O título da página deixa de importar (o título antigo
 * "Modelo · …" continua a funcionar como alternativa).
 *
 * @since 2.6.0
 */
final class ModelRoles
{
    public const META = '_elementor_page_settings';
    public const ROLE = 'fs_model_role';
    private const CACHE = 'flashsite_model_roles';

    public const POPUP_DEFAULTS = [
        'fs_popup_trigger' => 'delay',     // delay | exit | scroll
        'fs_popup_delay' => 30,            // segundos de navegação na visita
        'fs_popup_scroll' => 50,           // % da página
        'fs_popup_frequency' => 'days',    // days | session | always
        'fs_popup_days' => 7,              // dias sem repetir depois de fechado
        'fs_popup_where' => 'all',         // all | home | not_home | collections
        'fs_popup_device' => 'all',        // all | desktop | mobile
    ];

    /** @var array<string, list<int>>|null */
    private ?array $index = null;

    /** @var array<string, CollectionPresetInterface> */
    private array $presets = [];

    /** @param array<string, CollectionPresetInterface> $presets */
    public function register(array $presets): void
    {
        $this->presets = $presets;
        add_action('elementor/documents/register_controls', [$this, 'registerControls']);
        foreach (['added_post_meta', 'updated_post_meta', 'deleted_post_meta'] as $hook) {
            add_action($hook, [$this, 'maybeFlush'], 10, 3);
        }
        add_action('save_post_page', [$this, 'flush']);
        add_action('trashed_post', [$this, 'flush']);
        add_action('untrashed_post', [$this, 'flush']);
        add_action('deleted_post', [$this, 'flush']);
    }

    /** @return list<int> páginas com este papel (mais antigas primeiro) */
    public function pages(string $role): array
    {
        return $this->index()[$role] ?? [];
    }

    public function first(string $role): int
    {
        return $this->pages($role)[0] ?? 0;
    }

    public function roleOf(int $pageId): string
    {
        $settings = $this->settings($pageId);
        return is_string($settings[self::ROLE] ?? null) ? (string) $settings[self::ROLE] : '';
    }

    /** @return array<string, mixed> */
    public function popupSettings(int $pageId): array
    {
        $settings = $this->settings($pageId);
        $out = [];
        foreach (self::POPUP_DEFAULTS as $key => $default) {
            $value = $settings[$key] ?? $default;
            $out[$key] = is_int($default) ? max(0, (int) (is_array($value) ? ($value['size'] ?? $default) : $value)) : (string) $value;
        }
        return $out;
    }

    /** @return array<string, mixed> */
    public function settings(int $pageId): array
    {
        $settings = $pageId > 0 ? get_post_meta($pageId, self::META, true) : [];
        return is_array($settings) ? $settings : [];
    }

    /** @return array<string, list<int>> */
    private function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }
        $cached = get_transient(self::CACHE);
        if (is_array($cached)) {
            return $this->index = $cached;
        }
        $ids = get_posts([
            'post_type' => 'page',
            'post_status' => ['draft', 'private', 'publish', 'pending'],
            'posts_per_page' => 200,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'suppress_filters' => true,
            'meta_query' => [['key' => self::META, 'value' => self::ROLE, 'compare' => 'LIKE']],
        ]);
        $index = [];
        foreach ((array) $ids as $id) {
            $role = $this->roleOf((int) $id);
            if ($role !== '') {
                $index[$role][] = (int) $id;
            }
        }
        set_transient(self::CACHE, $index, DAY_IN_SECONDS);
        return $this->index = $index;
    }

    public function flush(): void
    {
        $this->index = null;
        delete_transient(self::CACHE);
    }

    public function maybeFlush($metaId, $postId, $metaKey): void
    {
        if ($metaKey === self::META) {
            $this->flush();
        }
    }

    /** @return array<string, string> */
    public function roleOptions(): array
    {
        $options = [
            '' => 'Página normal',
            'site:popup' => 'Popup',
            'site:404' => 'Página 404',
            'site:header' => 'Cabeçalho (páginas de texto)',
            'site:footer' => 'Rodapé (páginas de texto)',
        ];
        foreach ($this->presets as $preset) {
            $labels = $preset->labels();
            $singular = (string) ($labels['singular'] ?? $preset->key());
            $plural = (string) ($labels['plural'] ?? $preset->key());
            $options[$preset->key() . ':item'] = sprintf('%s · página de cada %s', $plural, mb_strtolower($singular));
            $options[$preset->key() . ':card'] = sprintf('%s · cartão da listagem', $plural);
            $options[$preset->key() . ':archive_top'] = sprintf('%s · topo da listagem', $plural);
            $options[$preset->key() . ':archive_bottom'] = sprintf('%s · fim da listagem', $plural);
        }
        return $options;
    }

    /** Secção "FlashSite · Página modelo" nas Definições da página (só páginas). */
    public function registerControls($document): void
    {
        if (! is_object($document) || ! method_exists($document, 'start_controls_section') || ! class_exists('\\Elementor\\Controls_Manager')) {
            return;
        }
        $post = method_exists($document, 'get_main_post') ? $document->get_main_post() : null;
        if (! $post instanceof \WP_Post || $post->post_type !== 'page') {
            return;
        }
        $popup = ['fs_model_role' => 'site:popup'];
        $document->start_controls_section('flashsite_model', [
            'label' => 'FlashSite · Página modelo',
            'tab' => \Elementor\Controls_Manager::TAB_SETTINGS,
        ]);
        $document->add_control(self::ROLE, [
            'label' => 'Usar esta página como',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => $this->roleOptions(),
            'default' => '',
            'description' => 'Páginas modelo podem ficar em rascunho: o FlashSite Core usa-as no site.',
        ]);
        $document->add_control('fs_popup_trigger', [
            'label' => 'Abrir',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => ['delay' => 'Após X segundos de navegação', 'exit' => 'Ao tentar sair (computador)', 'scroll' => 'Ao percorrer X% da página'],
            'default' => 'delay',
            'condition' => $popup,
        ]);
        $document->add_control('fs_popup_delay', [
            'label' => 'Segundos',
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 30, 'min' => 0, 'max' => 600,
            'condition' => $popup + ['fs_popup_trigger' => 'delay'],
        ]);
        $document->add_control('fs_popup_scroll', [
            'label' => '% da página',
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 50, 'min' => 1, 'max' => 100,
            'condition' => $popup + ['fs_popup_trigger' => 'scroll'],
        ]);
        $document->add_control('fs_popup_frequency', [
            'label' => 'Repetir',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => ['days' => 'Uma vez por visita; fechado, volta só após X dias', 'session' => 'Uma vez por visita', 'always' => 'Em todas as páginas'],
            'default' => 'days',
            'condition' => $popup,
        ]);
        $document->add_control('fs_popup_days', [
            'label' => 'Dias',
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 7, 'min' => 0, 'max' => 365,
            'condition' => $popup + ['fs_popup_frequency' => 'days'],
        ]);
        $document->add_control('fs_popup_where', [
            'label' => 'Mostrar em',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => ['all' => 'Todo o site', 'home' => 'Só na página inicial', 'not_home' => 'Todo o site menos a inicial', 'collections' => 'Só nas listagens e itens das coleções'],
            'default' => 'all',
            'condition' => $popup,
        ]);
        $document->add_control('fs_popup_device', [
            'label' => 'Dispositivos',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => ['all' => 'Todos', 'desktop' => 'Só computador', 'mobile' => 'Só telemóvel e tablet'],
            'default' => 'all',
            'condition' => $popup,
        ]);
        $top = array_values(array_filter(array_keys($this->roleOptions()), static fn ($r) => str_ends_with((string) $r, ':archive_top')));
        if ($top !== []) {
            $document->add_control('fs_archive_columns', [
                'label' => 'Colunas da listagem',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => ['' => 'Padrão da coleção', '1' => '1 (lista)', '2' => '2', '3' => '3', '4' => '4'],
                'default' => '',
                'condition' => [self::ROLE => $top],
            ]);
            $document->add_control('fs_archive_nav', [
                'label' => 'Atalhos por categoria no topo',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => ['' => 'Padrão da coleção', 'yes' => 'Mostrar', 'no' => 'Esconder'],
                'default' => '',
                'description' => 'Só em listagens agrupadas (ex.: Menu). Botões fixos que levam a cada categoria.',
                'condition' => [self::ROLE => $top],
            ]);
        }
        $document->end_controls_section();
    }
}
