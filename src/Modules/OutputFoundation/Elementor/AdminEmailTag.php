<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class AdminEmailTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-admin-email'; }
    protected function getTagTitle(): string { return 'FlashSite: E-mail Administrativo'; }
    protected function getTagPath(): string { return 'contact.email_admin'; }
}
