<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Fonts;

/**
 * Fontes locais (@since 3.2.0-beta.6): o que baixar e como escrever o CSS.
 *
 * Hierarquia padrão de design: a fonte dos títulos leva 700 e 800; a do texto leva 400, 600 e 700.
 * Quem monta o site pode refinar os pesos na tela FlashSite › Fontes. Só o alfabeto latino
 * (português, inglês, francês, espanhol). Cada fonte ganha uma reserva com as mesmas medidas.
 */
final class FontPlan
{
    public const ROLE_TITLE = 'titulo';
    public const ROLE_TEXT = 'texto';
    public const WEIGHTS = [100, 200, 300, 400, 500, 600, 700, 800, 900];

    /** @var array<string, list<int>> */
    public const DEFAULT_WEIGHTS = [
        self::ROLE_TITLE => [700, 800],
        self::ROLE_TEXT => [400, 600, 700],
    ];

    /** Papel pela etiqueta da variável: "surf-fonte-titulo", "heading", "display" → títulos; o resto → texto. */
    public static function roleFromLabel(string $label): string
    {
        return preg_match('/titul|title|heading|display|destaque/i', $label) ? self::ROLE_TITLE : self::ROLE_TEXT;
    }

    /**
     * Variáveis de fonte do Elementor V4 guardadas no kit (meta _elementor_global_variables).
     *
     * @param mixed $record JSON decodificado ou string
     * @return array<string, string> etiqueta => família
     */
    public static function fontVariables(mixed $record): array
    {
        if (is_string($record)) {
            $record = json_decode($record, true);
        }
        $out = [];
        foreach ((array) (is_array($record) ? ($record['data'] ?? []) : []) as $variable) {
            if (! is_array($variable) || ($variable['type'] ?? '') !== 'global-font-variable' || ! empty($variable['deleted'])) {
                continue;
            }
            $label = trim((string) ($variable['label'] ?? ''));
            $family = self::cleanFamily(self::scalar($variable['value'] ?? ''));
            if ($label !== '' && $family !== '') {
                $out[$label] = $family;
            }
        }
        return $out;
    }

    /** O Elementor pode guardar o valor tipado: {"$$type": "string", "value": "Source Sans 3"}. */
    private static function scalar(mixed $value): string
    {
        for ($i = 0; $i < 4 && is_array($value); $i++) {
            $value = $value['value'] ?? reset($value);
        }
        return is_scalar($value) ? (string) $value : '';
    }

    public static function cleanFamily(string $family): string
    {
        $family = trim(explode(',', $family)[0]);
        return trim($family, " \"'");
    }

    /** @param list<int|string> $weights @return list<int> */
    public static function cleanWeights(array $weights): array
    {
        $clean = array_values(array_intersect(self::WEIGHTS, array_map('intval', $weights)));
        sort($clean);
        return $clean;
    }

    /** Endereço do CSS do Google só com os pesos pedidos. */
    public static function googleCssUrl(string $family, array $weights): string
    {
        $weights = self::cleanWeights($weights) ?: [400];
        return 'https://fonts.googleapis.com/css2?family=' . str_replace('%20', '+', rawurlencode($family)) . ':wght@' . implode(';', $weights) . '&display=swap';
    }

    /**
     * Blocos @font-face do subset latin no CSS do Google.
     *
     * @return list<array{family: string, style: string, weight: int, url: string, range: string}>
     */
    public static function parseGoogleCss(string $css): array
    {
        $faces = [];
        if (! preg_match_all('#/\*\s*latin\s*\*/\s*@font-face\s*\{(.*?)\}#s', $css, $blocks)) {
            return [];
        }
        foreach ($blocks[1] as $block) {
            preg_match("#font-family:\s*'([^']+)'#", $block, $family);
            preg_match('#font-style:\s*(\w+)#', $block, $style);
            preg_match('#font-weight:\s*(\d+)#', $block, $weight);
            preg_match('#url\((https://[^)\s]+\.woff2)\)#', $block, $url);
            preg_match('#unicode-range:\s*([^;]+);#', $block, $range);
            if (isset($family[1], $weight[1], $url[1])) {
                $faces[] = ['family' => $family[1], 'style' => $style[1] ?? 'normal', 'weight' => (int) $weight[1], 'url' => $url[1], 'range' => trim($range[1] ?? '')];
            }
        }
        return $faces;
    }

    /**
     * Junta pesos que vêm do mesmo ficheiro (fonte variável): um @font-face com intervalo "400 700".
     *
     * @param list<array{family: string, style: string, weight: int, url: string, range: string}> $faces
     * @return list<array{family: string, style: string, weights: list<int>, url: string, range: string}>
     */
    public static function groupFaces(array $faces): array
    {
        $groups = [];
        foreach ($faces as $face) {
            $key = $face['url'] . '|' . $face['style'];
            $groups[$key] ??= ['family' => $face['family'], 'style' => $face['style'], 'weights' => [], 'url' => $face['url'], 'range' => $face['range']];
            $groups[$key]['weights'][] = $face['weight'];
        }
        foreach ($groups as &$group) {
            $group['weights'] = array_values(array_unique($group['weights']));
            sort($group['weights']);
        }
        return array_values($groups);
    }

    /**
     * CSS final: @font-face locais, fonte de reserva com medidas e as variáveis do Elementor com a pilha completa.
     *
     * @param array<string, array{role: string, faces: list<array{weights: list<int>, style: string, file_url: string, range: string}>}> $families
     * @param array<string, string> $variables etiqueta => família
     * @param array<string, array{fallback: string, size_adjust: float, ascent: float, descent: float, line_gap: float}> $metrics
     */
    public static function css(array $families, array $variables, array $metrics): string
    {
        $css = '';
        foreach ($families as $family => $data) {
            foreach ($data['faces'] as $face) {
                $weights = $face['weights'];
                $weight = count($weights) > 1 ? min($weights) . ' ' . max($weights) : (string) ($weights[0] ?? 400);
                $css .= sprintf(
                    '@font-face{font-family:"%s";font-style:%s;font-weight:%s;font-display:swap;src:url(%s) format("woff2");%s}',
                    $family, $face['style'], $weight, $face['file_url'], $face['range'] !== '' ? 'unicode-range:' . $face['range'] . ';' : ''
                );
            }
            $css .= self::fallbackFace($family, $metrics[$family] ?? null);
        }
        $vars = [];
        foreach ($variables as $label => $family) {
            if (isset($families[$family])) {
                $vars[] = '--' . preg_replace('/[^a-zA-Z0-9_-]/', '', $label) . ':' . self::stack($family, $metrics[$family] ?? null);
            }
        }
        if ($vars !== []) {
            $css .= 'html:root{' . implode(';', $vars) . '}';
        }
        return $css;
    }

    public static function stack(string $family, ?array $metric): string
    {
        $fallback = (string) ($metric['fallback'] ?? 'Arial');
        $generic = $fallback === 'Times New Roman' ? 'serif' : 'sans-serif';
        return sprintf('"%s","%s Reserva",%s,%s', $family, $family, $fallback === 'Arial' ? 'Arial' : '"' . $fallback . '"', $generic);
    }

    private static function fallbackFace(string $family, ?array $metric): string
    {
        $fallback = (string) ($metric['fallback'] ?? 'Arial');
        $local = $fallback === 'Times New Roman'
            ? 'local("Times New Roman"),local("TimesNewRomanPSMT"),local("Times")'
            : 'local("Arial"),local("ArialMT"),local("Helvetica")';
        $adjust = $metric === null ? '' : sprintf(
            'size-adjust:%s%%;ascent-override:%s%%;descent-override:%s%%;line-gap-override:%s%%;',
            $metric['size_adjust'], $metric['ascent'], $metric['descent'], $metric['line_gap']
        );
        return sprintf('@font-face{font-family:"%s Reserva";src:%s;%s}', $family, $local, $adjust);
    }
}
