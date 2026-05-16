<?php
declare(strict_types=1);

$errors = get_transient('flashsite_wizard_errors');
if (is_array($errors)) {
    delete_transient('flashsite_wizard_errors');
} else {
    $errors = [];
}

$headerTitle = 'Setup Wizard';
$headerSubtitle = 'Configure a base operacional do site, valide dependências e conclua o onboarding inicial.';
$headerActions = [
    [
        'label' => 'Dashboard',
        'url' => admin_url('admin.php?page=flashsite-core'),
        'variant' => 'secondary',
    ],
    [
        'label' => 'Dados do negócio',
        'url' => admin_url('admin.php?page=flashsite-business-data'),
        'variant' => 'secondary',
    ],
];
?>
<div class="wrap flashsite-core-wrap">
    <?php include FLASHSITE_CORE_PATH . 'templates/admin/partials/admin-header.php'; ?>

    <ol class="fsc-steps">
        <?php $index = 1; ?>
        <?php foreach ($steps as $key => $stepObject) : ?>
            <?php
            $classes = [];
            if ($currentStep === $key) {
                $classes[] = 'is-current';
            }
            if (! empty($completedSteps[$key])) {
                $classes[] = 'is-complete';
            }
            ?>
            <li class="<?php echo esc_attr(implode(' ', $classes)); ?>">
                <span class="fsc-step-number"><?php echo esc_html((string) $index); ?></span>
                <span class="fsc-step-label"><?php echo esc_html($stepObject->getLabel()); ?></span>
            </li>
            <?php $index++; ?>
        <?php endforeach; ?>
    </ol>

    <?php if (! empty($errors)) : ?>
        <div class="notice notice-error"><p>Existem erros no formulário. Revise os campos abaixo.</p></div>
    <?php endif; ?>

    <?php
    switch ($currentStep) {
        case 'business-data':
            include FLASHSITE_CORE_PATH . 'templates/admin/partials/wizard-step-business.php';
            break;
        case 'dependencies':
            include FLASHSITE_CORE_PATH . 'templates/admin/partials/wizard-step-dependencies.php';
            break;
        case 'summary':
            include FLASHSITE_CORE_PATH . 'templates/admin/partials/wizard-step-summary.php';
            break;
        default:
            include FLASHSITE_CORE_PATH . 'templates/admin/partials/wizard-step-welcome.php';
    }
    ?>
</div>
