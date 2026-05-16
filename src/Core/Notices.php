<?php
declare(strict_types=1);

namespace FlashSite\Core\Core;

final class Notices
{
    /** @var array<int, array{type: string, message: string, dismissible: bool}> */
    private array $queue = [];

    public function boot(): void
    {
        add_action('admin_notices', [$this, 'render']);
    }

    public function add(string $type, string $message, bool $dismissible = true): void
    {
        $this->queue[] = ['type' => $type, 'message' => $message, 'dismissible' => $dismissible];
    }

    public function render(): void
    {
        foreach ($this->queue as $notice) {
            $classes = ['notice', 'notice-' . sanitize_html_class($notice['type'])];
            if ($notice['dismissible']) {
                $classes[] = 'is-dismissible';
            }
            printf('<div class="%s"><p>%s</p></div>', esc_attr(implode(' ', $classes)), esc_html($notice['message']));
        }
    }
}
