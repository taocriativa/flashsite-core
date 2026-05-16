<?php
declare(strict_types=1);

$formatPhone = static function (string $value): string {
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if ($digits === '') {
        return 'Não definido';
    }
    if (strlen($digits) === 12 && str_starts_with($digits, '351')) {
        return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 3) . ' ' . substr($digits, 9, 3);
    }
    if (strlen($digits) === 9) {
        return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 3);
    }
    return $value;
};
$displayValue = static fn (string $value): string => $value !== '' ? $value : 'Não definido';

$headerTitle = 'Gestão FlashSite';
$headerSubtitle = 'Painel central para dados do negócio, configuração inicial, design controlado e segurança da entrega.';
$headerActions = [
    [
        'label' => 'Dados do negócio',
        'url' => admin_url('admin.php?page=flashsite-business-data'),
        'variant' => 'secondary',
    ],
    [
        'label' => 'Abrir Setup Wizard',
        'url' => admin_url('admin.php?page=flashsite-setup-wizard'),
        'variant' => 'secondary',
    ],
];
$onboardingStatusClass = $state->isCompleted() ? 'fsc-pill fsc-pill--success' : 'fsc-pill fsc-pill--warning';
$onboardingStatusLabel = $state->isCompleted() ? 'Onboarding concluído' : 'Onboarding em progresso';
?>
<div class="wrap flashsite-core-wrap">
    <?php include FLASHSITE_CORE_PATH . 'templates/admin/partials/admin-header.php'; ?>
    <div class="fsc-card-grid">
        <div class="fsc-card fsc-card--highlight fsc-onboarding-meta">
            <h2>Estado do onboarding</h2>
            <p><span class="<?php echo esc_attr($onboardingStatusClass); ?>"><?php echo esc_html($onboardingStatusLabel); ?></span></p>
            <div class="fsc-metric-list">
                <div class="fsc-metric"><span class="fsc-metric__label">Iniciado</span><span class="fsc-metric__value"><?php echo $state->isStarted() ? 'Sim' : 'Não'; ?></span></div>
                <div class="fsc-metric"><span class="fsc-metric__label">Concluído</span><span class="fsc-metric__value"><?php echo $state->isCompleted() ? 'Sim' : 'Não'; ?></span></div>
                <div class="fsc-metric"><span class="fsc-metric__label">Etapa atual</span><span class="fsc-metric__value"><?php echo esc_html($state->getCurrentStep()); ?></span></div>
            </div>
            <div class="fsc-card-actions">
                <a class="button button-primary fsc-btn" href="<?php echo esc_url(admin_url('admin.php?page=flashsite-setup-wizard')); ?>">Abrir Setup Wizard</a>
            </div>
        </div>

        <div class="fsc-card fsc-business-overview">
            <h2>Dados do negócio</h2>
            <div class="fsc-metric-list">
                <div class="fsc-metric"><span class="fsc-metric__label">Nome</span><span class="fsc-metric__value"><?php echo esc_html($displayValue($profile->getBusinessName())); ?></span></div>
                <div class="fsc-metric"><span class="fsc-metric__label">Área</span><span class="fsc-metric__value"><?php echo esc_html($displayValue($profile->getBusinessType())); ?></span></div>
                <div class="fsc-metric"><span class="fsc-metric__label">Telefone</span><span class="fsc-metric__value"><?php echo esc_html($formatPhone($profile->getPhone())); ?></span></div>
                <div class="fsc-metric"><span class="fsc-metric__label">Email</span><span class="fsc-metric__value"><?php echo esc_html($displayValue($profile->getEmail())); ?></span></div>
                <div class="fsc-metric"><span class="fsc-metric__label">Localidade</span><span class="fsc-metric__value"><?php echo esc_html($displayValue($profile->getCityRegion())); ?></span></div>
            </div>
            <div class="fsc-card-actions">
                <a class="button fsc-btn" href="<?php echo esc_url(admin_url('admin.php?page=flashsite-business-data')); ?>">Editar dados do negócio</a>
            </div>
        </div>
    </div>
</div>
