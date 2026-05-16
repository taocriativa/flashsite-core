<?php
declare(strict_types=1);

$socialLinks = $profile->getSocialLinks();
$hours = $profile->getHours();
$professional = $profile->getProfessional();
$branding = $profile->getBranding();
$displayValue = static function (string $value): string {
    return $value !== '' ? $value : '—';
};
$formatPhone = static function (string $value) use ($displayValue): string {
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if ($digits === '') {
        return $displayValue('');
    }
    if (strlen($digits) === 12 && str_starts_with($digits, '351')) {
        return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 3) . ' ' . substr($digits, 9, 3);
    }
    if (strlen($digits) === 9) {
        return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 3);
    }
    return $value;
};
$listToText = static function (array $items): string {
    return $items !== [] ? implode("\n", $items) : '—';
};
$brandingPreview = static function (int $attachmentId): string {
    if ($attachmentId <= 0) {
        return '—';
    }
    $thumb = wp_get_attachment_image($attachmentId, [180, 60], false, [
        'loading' => 'lazy',
        'decoding' => 'async',
        'alt' => '',
        'style' => 'max-width:180px;height:auto;display:block;',
    ]);
    if (is_string($thumb) && $thumb !== '') {
        return $thumb;
    }
    return 'Attachment ID ' . esc_html((string) $attachmentId);
};
$headerTitle = 'Dados do Negócio';
$headerSubtitle = 'Resumo operacional dos dados atuais. Use o Setup Wizard para editar ou completar a informação base do site.';
$headerActions = [
    [
        'label' => 'Voltar ao dashboard',
        'url' => admin_url('admin.php?page=flashsite-core'),
        'variant' => 'secondary',
    ],
    [
        'label' => 'Editar no Setup Wizard',
        'url' => admin_url('admin.php?page=flashsite-setup-wizard&step=business-data'),
        'variant' => 'secondary',
    ],
];
?>
<div class="wrap flashsite-core-wrap">
    <?php include FLASHSITE_CORE_PATH . 'templates/admin/partials/admin-header.php'; ?>

    <div class="fsc-card-grid fsc-business-summary-grid">
        <div class="fsc-card">
            <h2>Identificação</h2>
            <table class="fsc-summary-table" role="presentation">
                <tbody>
                    <tr><th>Nome</th><td><?php echo esc_html($displayValue($profile->getBusinessName())); ?></td></tr>
                    <tr><th>Área</th><td><?php echo esc_html($displayValue($profile->getBusinessType())); ?></td></tr>
                    <tr><th>Tagline</th><td><?php echo esc_html($displayValue($profile->getTagline())); ?></td></tr>
                    <tr><th>NIF</th><td><?php echo esc_html($displayValue($profile->getNif())); ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="fsc-card">
            <h2>Contacto</h2>
            <table class="fsc-summary-table" role="presentation">
                <tbody>
                    <tr><th>Telefone</th><td><?php echo esc_html($formatPhone($profile->getPhone())); ?></td></tr>
                    <tr><th>WhatsApp</th><td><?php echo esc_html($formatPhone($profile->getWhatsapp())); ?></td></tr>
                    <tr><th>Link WhatsApp</th><td><?php echo esc_html($displayValue($profile->getWhatsappLink())); ?></td></tr>
                    <tr><th>Email público</th><td><?php echo esc_html($displayValue($profile->getEmail())); ?></td></tr>
                    <tr><th>Email administrativo</th><td><?php echo esc_html($displayValue($profile->getAdminEmail())); ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="fsc-card">
            <h2>Localização</h2>
            <table class="fsc-summary-table" role="presentation">
                <tbody>
                    <tr><th>Morada</th><td><?php echo nl2br(esc_html($displayValue($profile->getAddress()))); ?></td></tr>
                    <tr><th>Cidade / Região</th><td><?php echo esc_html($displayValue($profile->getCityRegion())); ?></td></tr>
                    <tr><th>Código postal</th><td><?php echo esc_html($displayValue($profile->getPostalCode())); ?></td></tr>
                    <tr><th>País</th><td><?php echo esc_html($displayValue($profile->getCountry())); ?></td></tr>
                    <tr><th>Google Maps</th><td><?php echo esc_html($displayValue($profile->getGoogleMapsUrl())); ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="fsc-card">
            <h2>Presença digital</h2>
            <table class="fsc-summary-table" role="presentation">
                <tbody>
                    <tr><th>Website</th><td><?php echo esc_html($displayValue((string) ($socialLinks['website_url'] ?? ''))); ?></td></tr>
                    <tr><th>Instagram</th><td><?php echo esc_html($displayValue((string) ($socialLinks['instagram'] ?? ''))); ?></td></tr>
                    <tr><th>Facebook</th><td><?php echo esc_html($displayValue((string) ($socialLinks['facebook'] ?? ''))); ?></td></tr>
                    <tr><th>YouTube</th><td><?php echo esc_html($displayValue((string) ($socialLinks['youtube'] ?? ''))); ?></td></tr>
                    <tr><th>LinkedIn</th><td><?php echo esc_html($displayValue((string) ($socialLinks['linkedin'] ?? ''))); ?></td></tr>
                    <tr><th>TikTok</th><td><?php echo esc_html($displayValue((string) ($socialLinks['tiktok'] ?? ''))); ?></td></tr>
                    <tr><th>Doctoralia</th><td><?php echo esc_html($displayValue((string) ($socialLinks['doctoralia'] ?? ''))); ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="fsc-card">
            <h2>Horários</h2>
            <table class="fsc-summary-table" role="presentation">
                <tbody>
                    <tr><th>Segunda</th><td><?php echo esc_html($displayValue((string) ($hours['monday'] ?? ''))); ?></td></tr>
                    <tr><th>Terça</th><td><?php echo esc_html($displayValue((string) ($hours['tuesday'] ?? ''))); ?></td></tr>
                    <tr><th>Quarta</th><td><?php echo esc_html($displayValue((string) ($hours['wednesday'] ?? ''))); ?></td></tr>
                    <tr><th>Quinta</th><td><?php echo esc_html($displayValue((string) ($hours['thursday'] ?? ''))); ?></td></tr>
                    <tr><th>Sexta</th><td><?php echo esc_html($displayValue((string) ($hours['friday'] ?? ''))); ?></td></tr>
                    <tr><th>Sábado</th><td><?php echo esc_html($displayValue((string) ($hours['saturday'] ?? ''))); ?></td></tr>
                    <tr><th>Domingo</th><td><?php echo esc_html($displayValue((string) ($hours['sunday'] ?? ''))); ?></td></tr>
                    <tr><th>Observações</th><td><?php echo nl2br(esc_html($displayValue((string) ($hours['notes'] ?? $hours['legacy_text'] ?? '')))); ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="fsc-card">
            <h2>Dados profissionais</h2>
            <table class="fsc-summary-table" role="presentation">
                <tbody>
                    <tr><th>Nome de exibição</th><td><?php echo esc_html($displayValue((string) ($professional['display_name'] ?? ''))); ?></td></tr>
                    <tr><th>Título</th><td><?php echo esc_html($displayValue((string) ($professional['title'] ?? ''))); ?></td></tr>
                    <tr><th>Especialidade</th><td><?php echo esc_html($displayValue((string) ($professional['specialty'] ?? ''))); ?></td></tr>
                    <tr><th>Registo</th><td><?php echo esc_html($displayValue((string) ($professional['license'] ?? ''))); ?></td></tr>
                    <tr><th>ID secundário</th><td><?php echo esc_html($displayValue((string) ($professional['secondary_id'] ?? ''))); ?></td></tr>
                    <tr><th>Serviços</th><td><?php echo nl2br(esc_html($listToText((array) ($professional['services'] ?? [])))); ?></td></tr>
                    <tr><th>Planos aceitos</th><td><?php echo nl2br(esc_html($listToText((array) ($professional['accepted_plans'] ?? [])))); ?></td></tr>
                </tbody>
            </table>
        </div>
        <div class="fsc-card">
            <h2>Branding</h2>
            <table class="fsc-summary-table" role="presentation">
                <tbody>
                    <tr><th>Logo clara</th><td><?php echo $brandingPreview((int) ($branding['logo_light_id'] ?? 0)); ?></td></tr>
                    <tr><th>Logo escura</th><td><?php echo $brandingPreview((int) ($branding['logo_dark_id'] ?? 0)); ?></td></tr>
                    <tr><th>Cor principal</th><td><?php echo ($branding['primary_color'] ?? '') !== '' ? '<span class="fsc-color-swatch" style="background:' . esc_attr((string) $branding['primary_color']) . ';"></span>' . esc_html((string) $branding['primary_color']) : '—'; ?></td></tr>
                    <tr><th>Cor secundária</th><td><?php echo ($branding['secondary_color'] ?? '') !== '' ? '<span class="fsc-color-swatch" style="background:' . esc_attr((string) $branding['secondary_color']) . ';"></span>' . esc_html((string) $branding['secondary_color']) : '—'; ?></td></tr>
                    <tr><th>Cor de destaque</th><td><?php echo ($branding['accent_color'] ?? '') !== '' ? '<span class="fsc-color-swatch" style="background:' . esc_attr((string) $branding['accent_color']) . ';"></span>' . esc_html((string) $branding['accent_color']) : '—'; ?></td></tr>
                    <tr><th>Fonte principal</th><td><?php echo esc_html($displayValue((string) ($branding['font_primary'] ?? ''))); ?></td></tr>
                    <tr><th>Fonte secundária</th><td><?php echo esc_html($displayValue((string) ($branding['font_secondary'] ?? ''))); ?></td></tr>
                </tbody>
            </table>
        </div>

        <div class="fsc-card">
            <h2>Output Foundation</h2>
            <?php $shortcodes = [
                '[flashsite_business_name]',
                '[flashsite_phone]',
                '[flashsite_whatsapp]',
                '[flashsite_whatsapp_link]',
                '[flashsite_email]',
                '[flashsite_email_admin]',
                '[flashsite_address multiline="yes"]',
                '[flashsite_website]',
                '[flashsite_tagline]',
                '[flashsite_logo_light width="240"]',
                '[flashsite_logo_dark width="240"]',
                '[flashsite_business field="branding.primary_color"]',
            ]; ?>
            <div class="fsc-shortcode-list">
                <?php foreach ($shortcodes as $shortcode) : ?>
                    <div class="fsc-shortcode-item">
                        <code><?php echo esc_html($shortcode); ?></code>
                        <button type="button" class="button button-secondary fsc-btn fsc-copy-btn" data-copy-text="<?php echo esc_attr($shortcode); ?>">Copiar</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="description fsc-shortcode-note">Elementor passa a consumir estes dados via dynamic tags quando o builder estiver activo. O plugin continua funcional sem Elementor.</p>
        </div>

    </div>
</div>
