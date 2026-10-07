<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Aplica a camada de um mercado a um preset: o ficheiro do preset tem a base (Portugal) e,
 * em 'markets' => ['BR' => [...]], só o que muda no outro país.
 *
 *   labels, groups, settings  substituição por chave (um valor que é lista substitui a lista inteira)
 *   taxonomies                por taxonomia: chaves substituídas ('terms' troca a lista toda); null remove
 *   fields                    por campo: chaves substituídas; null remove; campo novo entra no fim
 *                             ou a seguir a 'after' => '<campo>'
 *   outras chaves (schema…)   substituídas
 *
 * @since 3.0.0
 */
final class MarketLayer
{
    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function apply(array $config, string $market): array
    {
        $layers = is_array($config['markets'] ?? null) ? $config['markets'] : [];
        unset($config['markets']);
        $layer = $layers[$market] ?? null;
        if (! is_array($layer) || $layer === []) {
            return $config;
        }
        foreach ($layer as $key => $value) {
            switch ($key) {
                case 'labels':
                case 'groups':
                case 'settings':
                    $config[$key] = array_replace((array) ($config[$key] ?? []), (array) $value);
                    break;
                case 'taxonomies':
                    $config[$key] = self::mergeMap((array) ($config[$key] ?? []), (array) $value);
                    break;
                case 'fields':
                    $config[$key] = self::mergeFields((array) ($config[$key] ?? []), (array) $value);
                    break;
                default:
                    $config[$key] = $value;
            }
        }
        return $config;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $over
     * @return array<string, mixed>
     */
    private static function mergeMap(array $base, array $over): array
    {
        foreach ($over as $k => $v) {
            if ($v === null) {
                unset($base[$k]);
            } elseif (is_array($v)) {
                $base[$k] = array_replace(is_array($base[$k] ?? null) ? $base[$k] : [], $v);
            }
        }
        return $base;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $over
     * @return array<string, mixed>
     */
    private static function mergeFields(array $base, array $over): array
    {
        $new = [];
        foreach ($over as $k => $v) {
            if ($v === null) {
                unset($base[$k]);
            } elseif (is_array($v) && isset($base[$k])) {
                $base[$k] = array_replace((array) $base[$k], $v);
            } elseif (is_array($v)) {
                $new[$k] = $v;
            }
        }
        foreach ($new as $k => $v) {
            $after = isset($v['after']) ? (string) $v['after'] : '';
            unset($v['after']);
            if ($after !== '' && array_key_exists($after, $base)) {
                $out = [];
                foreach ($base as $bk => $bv) {
                    $out[$bk] = $bv;
                    if ($bk === $after) {
                        $out[$k] = $v;
                    }
                }
                $base = $out;
            } else {
                $base[$k] = $v;
            }
        }
        return $base;
    }
}
