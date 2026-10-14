<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Abilities;

/**
 * Metadados comuns das Abilities FlashSite.
 *
 * "public" = exposta a clientes externos (WordPress 7.1). Não dispensa permission_callback:
 * execução anónima continua impossível. "mcp.public" cobre versões do MCP Adapter anteriores
 * à flag geral; quando as duas existem, a específica do canal prevalece e tem o mesmo valor.
 *
 * @since 3.5.0
 */
final class AbilityMeta
{
    public const CATEGORY = 'flashsite';

    /** @return array<string, mixed> */
    public static function meta(bool $readonly, bool $idempotent, bool $destructive = false): array
    {
        return [
            'public' => true,
            'mcp' => ['public' => true],
            'annotations' => [
                'readonly' => $readonly,
                'destructive' => $destructive,
                'idempotent' => $idempotent,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function error(string $code, string $message, int $status, array $data = []): \WP_Error
    {
        return new \WP_Error($code, $message, ['status' => $status] + $data);
    }

    /**
     * Converte o resultado ['ok' => false, ...] de um serviço de domínio em WP_Error.
     *
     * @param array<string, mixed> $result
     */
    public static function fromFailure(array $result): \WP_Error
    {
        $data = ['status' => (int) ($result['status'] ?? 400)];
        if (isset($result['errors'])) {
            $data['errors'] = $result['errors'];
        }
        return new \WP_Error((string) ($result['code'] ?? 'error'), (string) ($result['message'] ?? 'Erro.'), $data);
    }
}
