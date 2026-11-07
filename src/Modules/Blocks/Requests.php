<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Blocks;

/**
 * Pedidos recebidos pelo bloco Formulário (@since 3.6.1-blocks.3): cada envio fica guardado no painel
 * (FlashSite › Pedidos do site), mesmo que o e-mail falhe. Privado, sem página pública, sem REST.
 * Apagados automaticamente ao fim de 12 meses (RGPD/LGPD: guardar só o tempo necessário).
 */
final class Requests
{
    public const POST_TYPE = 'fs_pedido';
    public const CAP = 'flashsite_manage_business_data';
    private const CRON = 'flashsite_pedidos_limpeza';
    private const KEEP_DAYS = 365;

    public function register(): void
    {
        add_action('init', [$this, 'registerPostType']);
        add_action(self::CRON, [$this, 'purgeOld']);
        add_action('init', [$this, 'scheduleCleanup'], 20);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'column'], 10, 2);
    }

    public function registerPostType(): void
    {
        // Só em sites com tema de blocos: nos sites Elementor o menu não aparece.
        if (! function_exists('wp_is_block_theme') || ! wp_is_block_theme()) {
            return;
        }
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Pedidos do site', 'singular_name' => 'Pedido', 'menu_name' => 'Pedidos do site',
                'all_items' => 'Pedidos do site', 'edit_item' => 'Pedido', 'search_items' => 'Procurar pedidos',
                'not_found' => 'Ainda não chegou nenhum pedido pelo site.',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'flashsite-core',
            'show_in_rest' => false,
            'exclude_from_search' => true,
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'do_not_allow',
                'edit_post' => self::CAP, 'read_post' => self::CAP, 'delete_post' => self::CAP,
                'edit_posts' => self::CAP, 'edit_others_posts' => self::CAP, 'delete_posts' => self::CAP,
                'delete_others_posts' => self::CAP, 'publish_posts' => 'do_not_allow', 'read_private_posts' => self::CAP,
            ],
            'map_meta_cap' => false,
        ]);
    }

    /** @param list<string> $lines */
    public static function store(int $postId, string $formId, string $subject, array $lines, bool $sent): int
    {
        $title = $subject . ' · ' . wp_date('d/m/Y H:i');
        $id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'private',
            'post_title' => $title,
            'post_content' => implode("\n", $lines),
            'meta_input' => [
                '_fs_pedido_pagina' => $postId,
                '_fs_pedido_form' => $formId,
                '_fs_pedido_email' => $sent ? 'enviado' : 'falhou',
            ],
        ], true);
        return is_wp_error($id) ? 0 : (int) $id;
    }

    public function scheduleCleanup(): void
    {
        if (! post_type_exists(self::POST_TYPE)) {
            return;
        }
        if (! wp_next_scheduled(self::CRON)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON);
        }
    }

    public function purgeOld(): void
    {
        $old = get_posts([
            'post_type' => self::POST_TYPE, 'post_status' => 'any', 'fields' => 'ids', 'numberposts' => 200,
            'date_query' => [['before' => self::KEEP_DAYS . ' days ago']],
        ]);
        foreach ($old as $id) {
            wp_delete_post((int) $id, true);
        }
    }

    /** @param array<string, string> $columns */
    public function columns(array $columns): array
    {
        return ['cb' => $columns['cb'] ?? '', 'title' => 'Pedido', 'fs_resumo' => 'Dados', 'fs_email' => 'E-mail', 'date' => 'Data'];
    }

    public function column(string $column, int $postId): void
    {
        if ($column === 'fs_resumo') {
            echo nl2br(esc_html(wp_trim_words((string) get_post_field('post_content', $postId), 30)));
        } elseif ($column === 'fs_email') {
            echo get_post_meta($postId, '_fs_pedido_email', true) === 'enviado' ? 'Enviado' : '<strong>Falhou</strong>';
        }
    }
}
