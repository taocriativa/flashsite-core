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
?>
<div class="wrap fsc-wrap">
    <?php include __DIR__ . '/partials/admin-header.php'; ?>

    <div class="fsc-page-content">
        <div class="fsc-card">
            <h2>📋 Referência de Shortcodes</h2>
            <p class="description">
                Todos os shortcodes disponíveis no FlashSite Core.
                Clique em <strong>Copiar</strong> para copiar o shortcode e cole diretamente no Elementor (widget HTML ou Text Editor) ou no editor de páginas do WordPress.
            </p>
            <p class="description" style="margin-top:6px;">
                <strong>Nota:</strong> shortcodes marcados com <em>(widget HTML)</em> renderizam HTML estruturado — devem ser usados num widget HTML do Elementor, não num widget Texto.
            </p>
        </div>

        <?php foreach ($groups as $groupLabel => $items) : ?>
            <div class="fsc-card" style="margin-top:16px;">
                <h3><?php echo esc_html($groupLabel); ?></h3>
                <div class="fsc-shortcode-list">
                    <?php foreach ($items as [$sc, $desc]) : ?>
                        <div class="fsc-shortcode-item" style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid #f0f0f0;">
                            <code style="flex:0 0 auto;min-width:320px;"><?php echo esc_html($sc); ?></code>
                            <span class="description" style="flex:1;"><?php echo esc_html($desc); ?></span>
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
