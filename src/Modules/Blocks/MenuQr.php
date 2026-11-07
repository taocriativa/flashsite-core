<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Blocks;

use FlashSite\Core\Domain\Collections\CollectionRegistry;

/**
 * FlashSite › QR da carta (@since 3.7.0-beta.5): QR para imprimir nas mesas, a apontar para a lista
 * pública de uma coleção (ex.: /menu/). Gerado no navegador (biblioteca MIT qrcode-generator), em SVG,
 * sem serviço externo. Só aparece quando existe alguma coleção com página de lista.
 */
final class MenuQr
{
    public const SLUG = 'flashsite-qr';
    public const CAP = 'flashsite_manage_business_data';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addPage'], 40);
    }

    /** @return array<string, string> url => nome */
    private function targets(): array
    {
        $registry = BlocksModule::service(CollectionRegistry::class);
        $out = [];
        if (! $registry instanceof CollectionRegistry) {
            return $out;
        }
        foreach ($registry->all() as $preset) {
            $type = $preset->postType();
            if (! post_type_exists($type)) {
                continue;
            }
            $url = (string) get_post_type_archive_link($type);
            if ($url !== '') {
                $labels = $preset->labels();
                $out[$url] = (string) ($labels['plural'] ?? $type);
            }
        }
        return $out;
    }

    public function addPage(): void
    {
        if ($this->targets() === []) {
            return;
        }
        $hook = add_submenu_page('flashsite-core', 'QR da carta', 'QR da carta', self::CAP, self::SLUG, [$this, 'render']);
        if ($hook) {
            add_action('admin_enqueue_scripts', function (string $current) use ($hook): void {
                if ($current === $hook) {
                    wp_enqueue_script('flashsite-qrcode', FLASHSITE_CORE_URL . 'assets/admin/vendor/qrcode-generator-1.4.4.js', [], '1.4.4', true);
                }
            });
        }
    }

    public function render(): void
    {
        if (! current_user_can(self::CAP)) {
            wp_die('Sem permissão.');
        }
        $targets = $this->targets();
        $name = sanitize_title(wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES));
        ?>
        <div class="wrap">
            <h1>QR da carta</h1>
            <p>Para imprimir nas mesas ou na montra. Aponta para a página da carta no site, que se atualiza sozinha quando muda um prato ou um preço: não é preciso imprimir de novo.</p>
            <p>
                <label for="fs-qr-alvo"><strong>Página</strong></label><br>
                <select id="fs-qr-alvo">
                    <?php foreach ($targets as $url => $label) : ?>
                        <option value="<?php echo esc_attr($url); ?>"><?php echo esc_html($label . ' · ' . $url); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <div id="fs-qr" style="width:280px;height:280px;background:#fff;padding:16px;border:1px solid #dcdcde"></div>
            <p>
                <button type="button" class="button button-primary" id="fs-qr-baixar">Descarregar (SVG, para gráfica)</button>
                <button type="button" class="button" id="fs-qr-imprimir">Imprimir</button>
            </p>
            <p class="description">Teste sempre com o telemóvel antes de imprimir. Tamanho mínimo recomendado: 3 × 3 cm.</p>
        </div>
        <script>
        (function () {
            var alvo = document.getElementById('fs-qr-alvo'), box = document.getElementById('fs-qr'), svg = '';
            function desenha() {
                if (typeof qrcode !== 'function') { box.textContent = 'Não foi possível gerar o QR.'; return; }
                var q = qrcode(0, 'M'); q.addData(alvo.value); q.make();
                svg = q.createSvgTag({ cellSize: 8, margin: 2, scalable: true });
                box.innerHTML = svg;
            }
            alvo.addEventListener('change', desenha);
            window.addEventListener('load', desenha);
            document.getElementById('fs-qr-baixar').addEventListener('click', function () {
                var a = document.createElement('a');
                a.href = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml' }));
                a.download = <?php echo wp_json_encode('qr-' . ($name !== '' ? $name : 'carta') . '.svg'); ?>;
                a.click();
            });
            document.getElementById('fs-qr-imprimir').addEventListener('click', function () {
                var w = window.open('', '_blank'); if (!w) return;
                w.document.write('<title>QR</title><div style="width:7cm;margin:2cm auto;text-align:center;font-family:sans-serif">' + svg + '<p>' + alvo.value + '</p></div>');
                w.document.close(); w.focus(); w.print();
            });
        })();
        </script>
        <?php
    }
}
