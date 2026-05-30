<?php
declare(strict_types=1);
if (! defined('ABSPATH')) exit;

$groups = [
    '🏷️ Identificação' => [
        ['[flashsite_business_name]',      'Nome do Negócio'],
        ['[flashsite_business_type]',      'Tipo de Negócio'],
        ['[flashsite_tagline]',            'Tagline / Slogan'],
        ['[flashsite_tax_id]',             'NIF / CNPJ'],
    ],
    '📞 Contacto' => [
        ['[flashsite_phone]',              'Telefone (texto)'],
        ['[flashsite_phone_link]',         'Telefone (link tel:) — usar em href'],
        ['[flashsite_whatsapp]',           'WhatsApp — número'],
        ['[flashsite_whatsapp_link]',      'WhatsApp — link (wa.me)'],
        ['[flashsite_email]',              'E-mail público (texto)'],
        ['[flashsite_email_link]',         'E-mail (link mailto:) — usar em href'],
    ],
    '📍 Localização' => [
        ['[flashsite_address]',            'Endereço (linha única)'],
        ['[flashsite_address multiline="yes"]', 'Endereço (com quebras de linha)'],
        ['[flashsite_city_region]',        'Cidade / Região'],
        ['[flashsite_postal_code]',        'Código Postal / CEP'],
        ['[flashsite_country]',            'País'],
        ['[flashsite_full_address]',       'Endereço Completo (formatado)'],
        ['[flashsite_google_maps_url]',    'Google Maps — link'],
        ['[flashsite_maps]',               'Google Maps — embed iframe (widget HTML)'],
    ],
    '🕐 Horários' => [
        ['[flashsite_hours]',              'Horários — texto contínuo'],
        ['[flashsite_hours_list]',         'Horários — lista HTML ul/li (widget HTML)'],
    ],
    '📲 Redes Sociais' => [
        ['[flashsite_website]',            'Website'],
        ['[flashsite_instagram]',          'Instagram'],
        ['[flashsite_facebook]',           'Facebook'],
        ['[flashsite_youtube]',            'YouTube'],
        ['[flashsite_linkedin]',           'LinkedIn'],
        ['[flashsite_tiktok]',             'TikTok'],
        ['[flashsite_doctoralia]',         'Doctoralia'],
    ],
    '🩺 Dados Profissionais' => [
        ['[flashsite_prof_display_name]',  'Nome de Exibição (curto)'],
        ['[flashsite_prof_title]',         'Título Profissional'],
        ['[flashsite_prof_specialty]',     'Especialidade'],
        ['[flashsite_prof_license]',       'Registo Profissional (CRM/OAB/CAU)'],
        ['[flashsite_prof_secondary_id]',  'Registo Secundário (RQE)'],
        ['[flashsite_accepted_plans]',     'Planos / Convênios Aceitos — lista HTML (widget HTML)'],
    ],
    '🎨 Branding' => [
        ['[flashsite_logo_light width="240"]', 'Logo Clara (imagem)'],
        ['[flashsite_logo_dark width="240"]',  'Logo Escura (imagem)'],
    ],
    '📄 Política de Privacidade' => [
        ['[flashsite_privacy_policy]',     'Conteúdo completo da Política de Privacidade'],
        ['[flashsite_privacy_date]',       'Data da última atualização (d/m/Y)'],
        ['[flashsite_privacy_date format="Y-m-d"]', 'Data com formato personalizado'],
    ],
];

$headerTitle    = 'Short codes';
$headerSubtitle = 'Referência de todos os shortcodes disponíveis. Clique em Copiar e cole directamente no Elementor ou no editor de páginas.';
$headerActions  = [
    [
        'label'   => 'Voltar ao dashboard',
        'url'     => admin_url('admin.php?page=flashsite-core'),
        'variant' => 'secondary',
    ],
];
?>
<div class="wrap flashsite-core-wrap">
    <?php include FLASHSITE_CORE_PATH . 'templates/admin/partials/admin-header.php'; ?>

    <div class="fsc-card-grid" style="grid-template-columns:1fr;">

        <div class="fsc-card">
            <p class="description">
                Shortcodes marcados com <strong>(widget HTML)</strong> renderizam HTML estruturado — devem ser usados num widget HTML do Elementor, não num widget Texto.
            </p>
        </div>

        <?php foreach ($groups as $groupLabel => $items) : ?>
            <div class="fsc-card">
                <h3><?php echo esc_html($groupLabel); ?></h3>
                <div class="fsc-shortcode-list">
                    <?php foreach ($items as [$sc, $desc]) : ?>
                        <div class="fsc-shortcode-item">
                            <code><?php echo esc_html($sc); ?></code>
                            <span class="description"><?php echo esc_html($desc); ?></span>
                            <button type="button"
                                    class="button button-secondary fsc-btn fsc-copy-btn"
                                    data-copy-text="<?php echo esc_attr($sc); ?>">
                                Copiar
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
</div>
