<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Blocks;

use FlashSite\Core\Modules\Contact\ContactModule;

/**
 * Envio do bloco "Formulário Flash Site" (POST /wp-json/flashsite/v1/form).
 *
 * Os campos não vêm do visitante: são lidos do próprio bloco gravado na página (post + id do formulário),
 * por isso ninguém consegue enviar campos que o formulário não tem. Anti-spam: campo isco, tempo mínimo
 * de preenchimento e limite de envios por IP. O pedido vai por e-mail para o destinatário dos Dados do
 * Negócio e, se o formulário pedir, a resposta traz o link do WhatsApp com o resumo.
 */
final class FormHandler
{
    private const MIN_SECONDS = 3;
    private const MAX_PER_HOUR = 8;

    public function handle(\WP_REST_Request $request): \WP_REST_Response
    {
        $postId = (int) $request->get_param('post');
        $formId = sanitize_key((string) $request->get_param('form'));
        $values = (array) $request->get_param('fields');

        if ((string) $request->get_param('website') !== '') {
            return $this->ok(); // isco preenchido: robô; finge que correu bem
        }
        $started = (int) $request->get_param('t');
        if ($started > 0 && (time() - $started) < self::MIN_SECONDS) {
            return $this->error('Envio demasiado rápido. Tente de novo.', 429);
        }
        if (! $this->withinRateLimit()) {
            return $this->error('Muitos envios seguidos. Tente daqui a pouco.', 429);
        }

        $form = $postId > 0 && $formId !== '' ? self::findForm($postId, $formId) : null;
        if ($form === null) {
            return $this->error('Formulário não encontrado.', 404);
        }

        $lines = [];
        $errors = [];
        foreach (self::fieldsOf($form) as $field) {
            $name = $field['name'];
            $raw = isset($values[$name]) ? (string) $values[$name] : '';
            $value = $field['type'] === 'textarea' ? sanitize_textarea_field($raw) : sanitize_text_field($raw);
            if ($field['required'] && trim($value) === '') {
                $errors[$name] = 'Campo obrigatório.';
                continue;
            }
            if ($value !== '' && $field['type'] === 'email' && ! is_email($value)) {
                $errors[$name] = 'E-mail inválido.';
            }
            if ($value !== '' && $field['type'] === 'tel' && strlen((string) preg_replace('/\D+/', '', $value)) < 9) {
                $errors[$name] = 'Número de telefone inválido.';
            }
            if ($field['type'] === 'select' && $value !== '' && $field['options'] !== [] && ! in_array($value, $field['options'], true)) {
                $errors[$name] = 'Opção inválida.';
            }
            if ($value !== '') {
                $lines[] = $field['label'] . ': ' . $value;
            }
        }
        if ($errors !== []) {
            return new \WP_REST_Response(['ok' => false, 'errors' => $errors, 'message' => 'Verifique os campos assinalados.'], 422);
        }

        $attrs = (array) ($form['attrs'] ?? []);
        $subject = trim((string) ($attrs['subject'] ?? '')) ?: 'Novo pedido pelo site';
        $site = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
        $body = implode("\n", $lines) . "\n\nEnviado de: " . get_permalink($postId);

        $contact = BlocksModule::service(ContactModule::class);
        $to = $contact instanceof ContactModule ? $contact->recipientEmail() : (string) get_option('admin_email');
        $sent = wp_mail($to, '[' . $site . '] ' . $subject, $body);

        /** Ganchos para o CRM e outros registos. @since 3.6.0-blocks.1 */
        do_action('flashsite_form_submitted', $postId, $formId, $lines, $sent);

        if (! $sent) {
            return $this->error('Não foi possível enviar. Tente de novo.', 500);
        }

        $whatsapp = '';
        if (! empty($attrs['whatsapp']) && $contact instanceof ContactModule) {
            $digits = $contact->whatsappDigits();
            if ($digits !== '') {
                $intro = trim((string) ($attrs['whatsappIntro'] ?? '')) ?: 'Olá! Acabei de enviar este pedido pelo site:';
                $whatsapp = 'https://wa.me/' . $digits . '?text=' . rawurlencode($intro . "\n" . implode("\n", $lines));
            }
        }
        return new \WP_REST_Response(['ok' => true, 'whatsapp' => $whatsapp], 200);
    }

    /** @return array<string, mixed>|null o bloco flashsite/form com esse id */
    public static function findForm(int $postId, string $formId): ?array
    {
        $post = get_post($postId);
        if (! $post instanceof \WP_Post || $post->post_status !== 'publish') {
            return null;
        }
        $find = static function (array $blocks) use (&$find, $formId): ?array {
            foreach ($blocks as $block) {
                if (($block['blockName'] ?? '') === 'flashsite/form' && (string) ($block['attrs']['formId'] ?? '') === $formId) {
                    return $block;
                }
                if (! empty($block['innerBlocks'])) {
                    $hit = $find($block['innerBlocks']);
                    if ($hit !== null) {
                        return $hit;
                    }
                }
            }
            return null;
        };
        return $find(parse_blocks($post->post_content));
    }

    /** @return list<array{name: string, label: string, type: string, required: bool, options: list<string>}> */
    public static function fieldsOf(array $form): array
    {
        $out = [];
        $walk = static function (array $blocks) use (&$walk, &$out): void {
            foreach ($blocks as $block) {
                if (($block['blockName'] ?? '') === 'flashsite/form-field') {
                    $a = (array) ($block['attrs'] ?? []);
                    $label = (string) ($a['label'] ?? 'Campo');
                    $out[] = [
                        'name' => self::fieldName($a),
                        'label' => $label,
                        'type' => sanitize_key((string) ($a['type'] ?? 'text')),
                        'required' => ! empty($a['required']),
                        'options' => self::options((string) ($a['options'] ?? '')),
                    ];
                }
                if (! empty($block['innerBlocks'])) {
                    $walk($block['innerBlocks']);
                }
            }
        };
        $walk((array) ($form['innerBlocks'] ?? []));
        return $out;
    }

    /** @param array<string, mixed> $attrs */
    public static function fieldName(array $attrs): string
    {
        $name = sanitize_key((string) ($attrs['name'] ?? ''));
        return $name !== '' ? $name : sanitize_key(remove_accents((string) ($attrs['label'] ?? 'campo')));
    }

    /** @return list<string> */
    public static function options(string $raw): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: []), 'strlen'));
    }

    private function withinRateLimit(): bool
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $key = 'fs_form_' . md5($ip);
        $count = (int) get_transient($key);
        if ($count >= self::MAX_PER_HOUR) {
            return false;
        }
        set_transient($key, $count + 1, HOUR_IN_SECONDS);
        return true;
    }

    private function ok(): \WP_REST_Response
    {
        return new \WP_REST_Response(['ok' => true, 'whatsapp' => ''], 200);
    }

    private function error(string $message, int $status): \WP_REST_Response
    {
        return new \WP_REST_Response(['ok' => false, 'message' => $message], $status);
    }
}
