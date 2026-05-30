<?php
declare(strict_types=1);
namespace FlashSite\Core\Modules\OutputFoundation\Elementor;
final class EmailLinkTag extends AbstractBusinessUrlTag
{
    protected function getTagSlug(): string { return 'flashsite-business-email-link'; }
    protected function getTagTitle(): string { return 'FlashSite: E-mail (Link mailto:)'; }
    protected function getTagPath(): string { return '_flashsite.format.email_link'; }
}
