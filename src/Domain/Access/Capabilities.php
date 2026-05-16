<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Access;

final class Capabilities
{
    public const VIEW_DASHBOARD = 'flashsite_view_dashboard';
    public const MANAGE_BUSINESS_DATA = 'flashsite_manage_business_data';
    public const MANAGE_DEPENDENCIES = 'flashsite_manage_dependencies';
    public const RUN_SETUP_WIZARD = 'flashsite_run_setup_wizard';
}
