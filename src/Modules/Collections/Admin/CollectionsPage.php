<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Admin;

use FlashSite\Core\Domain\Collections\ActivationRepository;
use FlashSite\Core\Domain\Collections\CollectionCapabilities;
use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\CollectionSettings;
use FlashSite\Core\Domain\Collections\Currency;
use FlashSite\Core\Domain\Collections\Market;
use FlashSite\Core\Modules\Collections\CollectionStateService;
use FlashSite\Core\Modules\Collections\DemoKitImporter;

/**
 * FlashSite › Coleções: ligar/desligar presets e (em demos) importar os exemplos.
 * Só para quem tem flashsite_manage_collections (Administrador).
 *
 * @since 2.6.0
 */
final class CollectionsPage
{
    public const SLUG = 'flashsite-collections';
    private const NONCE = 'flashsite_collections_save';

    public function __construct(
        private CollectionRegistry $registry,
        private ActivationRepository $activation,
        private DemoKitImporter $importer,
        private ?CollectionSettings $settings = null,
        private ?CollectionStateService $state = null,
    ) {}

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu'], 20);
        add_action('admin_post_flashsite_collections_save', [$this, 'handleSave']);
        add_action('admin_post_flashsite_collections_demo', [$this, 'handleDemo']);
    }

    public function addMenu(): void
    {
        add_submenu_page('flashsite-core', 'Coleções', 'Coleções', CollectionCapabilities::MANAGE_CAP, self::SLUG, [$this, 'render']);
    }

    public function render(): void
    {
        if (! current_user_can(CollectionCapabilities::MANAGE_CAP)) {
            wp_die('Sem permissão.');
        }
        $notice = isset($_GET['fs_notice']) ? sanitize_text_field(wp_unslash((string) $_GET['fs_notice'])) : '';
        $headerTitle = 'Coleções';
        $headerSubtitle = 'Conteúdos que o cliente cadastra no painel e o site mostra sozinho. Desligar uma coleção esconde-a, mas nunca apaga os registos.';
        $headerActions = [['label' => 'Voltar ao dashboard', 'url' => admin_url('admin.php?page=flashsite-core'), 'variant' => 'secondary']];
        echo '<div class="wrap flashsite-core-wrap">';
        include FLASHSITE_CORE_PATH . 'templates/admin/partials/admin-header.php';
        if ($notice !== '') {
            printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($notice));
        }
        foreach ($this->registry->errors() as $source => $message) {
            printf('<div class="notice notice-error"><p>Preset <code>%s</code> ignorado: %s</p></div>', esc_html((string) $source), esc_html($message));
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="flashsite_collections_save">';
        wp_nonce_field(self::NONCE);
        $activeCount = count(array_filter($this->registry->keys(), fn (string $k): bool => $this->activation->isActive($k)));
        if ($activeCount === 0) {
            echo '<div class="notice notice-warning inline"><p><strong>Nenhuma coleção ativa.</strong> Marque a caixa da coleção que quer usar e carregue em "Guardar coleções".</p></div>';
        }
        echo '<div class="fsc-card-grid" style="grid-template-columns:1fr;">';
        echo '<div class="fsc-card"><h2>1. Coleções do site</h2>';
        echo '<table class="widefat striped"><thead><tr><th style="width:110px">Estado</th><th>Coleção</th><th>Endereço no site</th><th>Campos</th></tr></thead><tbody>';
        foreach ($this->registry->all() as $key => $preset) {
            $isActive = $this->activation->isActive((string) $key);
            printf(
                '<tr><td><label style="display:inline-flex;gap:6px;align-items:center;font-weight:600"><input type="checkbox" name="active[]" value="%1$s" id="fs-col-%1$s"%2$s> %6$s</label></td><td><label for="fs-col-%1$s"><strong>%3$s</strong></label>%7$s</td><td><code>/%4$s/</code>%8$s</td><td>%5$d</td></tr>',
                esc_attr((string) $key),
                $isActive ? ' checked' : '',
                esc_html($preset->labels()['plural']),
                esc_html($preset->slug()),
                count($preset->fields()),
                $isActive ? '<span style="color:#008a20">Ativa</span>' : '<span style="color:#8a8a8e">Desligada</span>',
                $this->registry->isExternal((string) $key) ? ' <span class="description">(extensão externa)</span>' : '',
                $isActive ? '' : $this->slugWarning($preset->slug())
            );
        }
        echo '</tbody></table></div>';

        $market = $this->settings?->market() ?? Market::DEFAULT;
        echo '<div class="fsc-card"><h2>2. País do site</h2><p>Define os termos e os campos das coleções (ex.: "T2" em Portugal, "2 quartos" no Brasil). Escolha antes de cadastrar conteúdos: os termos das listas são criados para este país.</p>';
        echo '<select name="market" id="fs-market">';
        foreach (Market::options() as $code => $label) {
            printf('<option value="%s"%s>%s</option>', esc_attr($code), $code === $market ? ' selected' : '', esc_html($label));
        }
        echo '</select>';
        $current = $this->settings?->currency()->code ?? Currency::DEFAULT;
        echo '<h2 style="margin-top:18px">3. Moeda dos preços</h2><p>Usada em todos os preços do site, nas faixas de preço e nos dados para o Google.</p>';
        echo '<select name="currency" id="fs-currency">';
        foreach (Currency::options() as $code => $label) {
            printf('<option value="%s"%s>%s</option>', esc_attr($code), $code === $current ? ' selected' : '', esc_html($label));
        }
        echo '</select>';
        if ($this->settings !== null && (bool) apply_filters('flashsite/multilingual_active', false)) {
            echo '<h2 style="margin-top:18px">4. Idiomas</h2><p>Compartilhada (padrão): cada item é cadastrado uma vez e o site traduz só os rótulos (preço, botões, filtros). Itens por idioma: cada item tem a sua versão em cada idioma, ligada pelo Polylang (botão + na lista de itens). Use quando o título e a descrição precisam de mudar de idioma.</p>';
            echo '<input type="hidden" name="fs_per_language_form" value="1">';
            foreach ($this->registry->all() as $key => $preset) {
                if (! $this->activation->isActive((string) $key)) {
                    continue;
                }
                printf('<p><label><input type="checkbox" name="per_language[]" value="%s"%s> %s: itens por idioma</label></p>', esc_attr((string) $key), $this->settings->perLanguage((string) $key) ? ' checked' : '', esc_html($preset->labels()['plural']));
            }
        }
        echo '<div class="fsc-card-actions">';
        submit_button('Guardar coleções', 'primary fsc-btn', 'submit', false);
        echo '</div></div></div>';
        echo '</form>';

        $withKit = array_filter($this->registry->all(), fn ($preset) => $this->activation->isActive($preset->key()) && $this->importer->hasKit($preset));
        if ($withKit !== []) {
            echo '<div class="fsc-card-grid" style="grid-template-columns:1fr;margin-top:20px"><div class="fsc-card"><h2>4. Exemplos para demonstração</h2><p>Cria registos fictícios para sites de demonstração. Não usar em sites de clientes.</p>';
            foreach ($withKit as $preset) {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin:0 8px 8px 0">';
                echo '<input type="hidden" name="action" value="flashsite_collections_demo">';
                printf('<input type="hidden" name="collection" value="%s">', esc_attr($preset->key()));
                wp_nonce_field(self::NONCE);
                printf('<button class="button button-secondary fsc-btn" name="op" value="import">Importar exemplos · %s</button> ', esc_html($preset->labels()['plural']));
                printf('<button class="button-link-delete" name="op" value="remove" onclick="return confirm(\'Mover os exemplos para o lixo?\')">Remover exemplos</button>');
                echo '</form>';
            }
            echo '</div></div>';
        }
        echo '</div>';
    }

    /**
     * Aviso (nunca bloqueio) quando já existe uma página publicada com o endereço da coleção:
     * ao ativar, o arquivo da coleção passa a responder nesse endereço.
     */
    private function slugWarning(string $slug): string
    {
        if (! function_exists('get_page_by_path')) {
            return '';
        }
        $page = get_page_by_path($slug);
        if (! $page instanceof \WP_Post || $page->post_status !== 'publish') {
            return '';
        }
        return '<div class="notice notice-warning inline" style="margin:6px 0 0"><p>Existe uma página publicada utilizando este slug. A ativação desta Collection pode alterar qual conteúdo responde nesta URL.</p></div>';
    }

    public function handleSave(): void
    {
        if (! current_user_can(CollectionCapabilities::MANAGE_CAP)) {
            wp_die('Sem permissão.');
        }
        check_admin_referer(self::NONCE);
        $active = isset($_POST['active']) && is_array($_POST['active']) ? array_map('sanitize_key', wp_unslash($_POST['active'])) : [];
        $keys = array_values($active);
        $this->state?->replace($keys) ?? $this->activation->replace($keys, $this->registry->keys());
        $currency = sanitize_text_field(wp_unslash((string) ($_POST['currency'] ?? '')));
        $market = sanitize_text_field(wp_unslash((string) ($_POST['market'] ?? '')));
        if ($this->settings !== null && Market::isValid($market)) {
            $before = $this->settings->market();
            $this->settings->setMarket($market);
            // Mudou de país e a moeda era a do país anterior: passa à do novo.
            if (Market::normalize($market) !== $before && $currency === Market::defaultCurrency($before)) {
                $currency = Market::defaultCurrency(Market::normalize($market));
            }
        }
        if ($currency !== '' && $this->settings !== null) {
            $this->settings->setCurrency($currency);
        }
        if ($this->settings !== null && isset($_POST['fs_per_language_form'])) {
            $perLanguage = isset($_POST['per_language']) && is_array($_POST['per_language']) ? array_map('sanitize_key', wp_unslash($_POST['per_language'])) : [];
            $this->settings->setPerLanguage(array_values(array_intersect($perLanguage, $this->registry->keys())));
        }
        CollectionStateService::notifyChanged();
        $this->redirect($active === []
            ? 'Guardado. Nenhuma coleção ficou ativa: marque a caixa da coleção antes de guardar.'
            : 'Coleções guardadas. Os menus aparecem no painel a seguir.');
    }

    public function handleDemo(): void
    {
        if (! current_user_can(CollectionCapabilities::MANAGE_CAP)) {
            wp_die('Sem permissão.');
        }
        check_admin_referer(self::NONCE);
        $preset = $this->registry->get(sanitize_key(wp_unslash((string) ($_POST['collection'] ?? ''))));
        if ($preset === null || ! $this->activation->isActive($preset->key())) {
            $this->redirect('Coleção inativa.');
        }
        $op = sanitize_key(wp_unslash((string) ($_POST['op'] ?? '')));
        if ($op === 'remove') {
            $count = $this->importer->remove($preset);
            $this->redirect(sprintf('%d exemplo(s) movido(s) para o lixo.', $count));
        }
        $result = $this->importer->import($preset);
        $message = sprintf('%d exemplo(s) criado(s), %d já existia(m).', $result['created'], $result['skipped']);
        if ($result['missing_images'] !== []) {
            $message .= ' Imagens não encontradas na Biblioteca: ' . implode(', ', $result['missing_images']) . '.';
        }
        $this->redirect($message);
    }

    private function redirect(string $notice): never
    {
        wp_safe_redirect(add_query_arg(['page' => self::SLUG, 'fs_notice' => rawurlencode($notice)], admin_url('admin.php')));
        exit;
    }
}
