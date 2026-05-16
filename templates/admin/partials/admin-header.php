<?php
declare(strict_types=1);

$headerTitle = isset($headerTitle) ? (string) $headerTitle : 'Gestão FlashSite';
$headerSubtitle = isset($headerSubtitle) ? (string) $headerSubtitle : '';
$headerActions = isset($headerActions) && is_array($headerActions) ? $headerActions : [];
?>
<div class="fsc-admin-header">
    <div class="fsc-admin-header__brand">
        <div class="fsc-admin-header__logo-wrap">
            <img class="fsc-admin-header__logo" src="<?php echo esc_url(FLASHSITE_CORE_URL . 'assets/admin/img/flashsite-logo.png'); ?>" alt="FlashSite">
        </div>
        <div class="fsc-admin-header__titles">
            <span class="fsc-admin-header__eyebrow">FlashSite · Painel de Gestão</span>
            <h1><?php echo esc_html($headerTitle); ?></h1>
            <?php if ($headerSubtitle !== '') : ?>
                <p><?php echo esc_html($headerSubtitle); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($headerActions !== []) : ?>
        <div class="fsc-admin-header__actions">
            <?php foreach ($headerActions as $action) : ?>
                <?php
                $label = isset($action['label']) ? (string) $action['label'] : '';
                $url = isset($action['url']) ? (string) $action['url'] : '';
                $variant = isset($action['variant']) ? (string) $action['variant'] : 'secondary';
                if ($label === '' || $url === '') {
                    continue;
                }
                $classes = $variant === 'primary' ? 'button button-primary fsc-btn' : 'button button-secondary fsc-btn';
                ?>
                <a class="<?php echo esc_attr($classes); ?>" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
