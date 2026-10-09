<?php
declare(strict_types=1);
if (! defined('ABSPATH')) exit;

/**
 * FlashSite › O que pode editar (@since 3.1.0).
 *
 * @var list<array{icon: string, title: string, text: string, links: list<array{label: string, url: string, primary?: bool}>}> $cards
 */

$user = wp_get_current_user();
$firstName = trim((string) ($user->first_name ?? '')) !== '' ? (string) $user->first_name : (string) $user->display_name;

$headerTitle    = 'O que pode editar';
$headerSubtitle = sprintf('Olá, %s. Tudo o que pode mudar no site está aqui. As alterações aparecem no site assim que guardar.', $firstName);
$headerActions  = [
    ['label' => 'Ver o site', 'url' => home_url('/'), 'variant' => 'primary'],
];
?>
<div class="wrap flashsite-core-wrap fsc-client-panel">
    <?php include FLASHSITE_CORE_PATH . 'templates/admin/partials/admin-header.php'; ?>

    <div class="fsc-card-grid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr));">
        <?php foreach ($cards as $card) : ?>
            <div class="fsc-card" style="display:flex;flex-direction:column;gap:10px;">
                <h2 style="margin:0;display:flex;align-items:center;gap:8px;font-size:17px;">
                    <span class="dashicons <?php echo esc_attr($card['icon']); ?>" aria-hidden="true"></span>
                    <?php echo esc_html($card['title']); ?>
                </h2>
                <p style="margin:0;color:#50575e;flex:1;"><?php echo esc_html($card['text']); ?></p>
                <p style="margin:4px 0 0;display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach ($card['links'] as $link) : ?>
                        <a class="button <?php echo ! empty($link['primary']) ? 'button-primary' : 'button-secondary'; ?> fsc-btn" href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
                    <?php endforeach; ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="description" style="margin-top:24px;">Precisa de mudar algo que não está aqui (uma secção nova, outra página, o design)? Fale com a Flash Site.</p>
</div>
