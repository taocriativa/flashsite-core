<?php
declare(strict_types=1);

namespace FlashSite\Core\Core;

use FlashSite\Core\Core\Contracts\LoggerInterface;

final class Logger implements LoggerInterface
{
    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context, true, true);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context, false, $this->isWpDebugLoggingEnabled());
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context, false, $this->isWpDebugLoggingEnabled());
    }

    public function debug(string $message, array $context = []): void
    {
        $enabled = defined('FLASHSITE_DEBUG') && FLASHSITE_DEBUG === true && $this->isWpDebugLoggingEnabled();
        $this->write('DEBUG', $message, $context, false, $enabled);
    }

    private function write(string $level, string $message, array $context, bool $useErrorLog, bool $useDebugLog): void
    {
        if (! $useErrorLog && ! $useDebugLog) {
            return;
        }
        $line = sprintf(
            '[%s] [FlashSite][%s] %s%s',
            gmdate('Y-m-d H:i:s'),
            $level,
            $message,
            $context !== [] ? ' | context: ' . wp_json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
        );
        if ($useErrorLog) {
            error_log($line);
        }
        if ($useDebugLog && ! $useErrorLog) {
            error_log($line);
        }
    }

    private function isWpDebugLoggingEnabled(): bool
    {
        return defined('WP_DEBUG') && WP_DEBUG === true && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;
    }
}
