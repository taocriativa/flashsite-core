<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class EmailTag extends AbstractBusinessTextTag
{
    protected function getTagSlug(): string { return 'flashsite-business-email'; }
    protected function getTagTitle(): string { return 'FlashSite: E-mail Público'; }
    protected function getTagPath(): string { return 'contact.email_public'; }
}
