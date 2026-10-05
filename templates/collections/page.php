<?php
/**
 * FlashSite Core · página de coleção (page) a partir de uma página modelo do Elementor.
 * Estrutura "Canvas": o cabeçalho e o rodapé vêm das próprias páginas modelo.
 *
 * @since 2.6.0
 */
declare(strict_types=1);

if (! defined('ABSPATH')) { exit; }

$flashsiteModelPages = \FlashSite\Core\Core\Plugin::instance()->application()?->container()->make(\FlashSite\Core\Modules\Collections\Output\ModelPages::class);
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class('fs-collection-page fs-collection-page--page'); ?>>
<?php wp_body_open(); ?>
<main id="content" class="fs-collection-main">
<?php
if ($flashsiteModelPages instanceof \FlashSite\Core\Modules\Collections\Output\ModelPages) {
    $flashsiteModelPages->renderTextPage();
}
?>
</main>
<?php wp_footer(); ?>
</body>
</html>
