<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Converte valores de entrada (formulário, REST, import) no valor canónico guardado em meta.
 *
 * Valores monetários aceitam o formato português ("285.000,00", "285 000,00 €", "1250,5")
 * e também o formato com ponto decimal ("1250.50"). São guardados como float com 2 casas.
 *
 * @since 2.6.0
 */
final class ItemSanitizer
{
    public function __construct(private ?CollectionSettings $settings = null) {}

    public function sanitize(FieldDefinition $field, mixed $value): mixed
    {
        $decimal = $this->settings?->currency()->decimalSeparator() ?? ',';

        return match ($field->type) {
            FieldType::Text, FieldType::Tel => $this->text($value, $field->maxLength),
            FieldType::Textarea => $this->textarea($value, $field->maxLength),
            FieldType::Richtext => is_scalar($value) ? wp_kses_post((string) $value) : '',
            FieldType::Number => self::parseNumber($value),
            FieldType::Money => ($n = self::parseNumber($value, $decimal)) === null ? null : round($n, 2),
            FieldType::Bool => $this->bool($value),
            FieldType::Select => $this->select($field, $value),
            FieldType::Multiselect => $this->multiselect($field, $value),
            FieldType::Date => $this->date($value),
            FieldType::Time => $this->time($value),
            FieldType::Url => is_scalar($value) ? esc_url_raw(trim((string) $value)) : '',
            FieldType::Email => is_scalar($value) ? sanitize_email((string) $value) : '',
            FieldType::Image, FieldType::File => max(0, absint(is_scalar($value) ? $value : 0)),
            FieldType::Gallery => $this->gallery($value),
            FieldType::Geo => $this->geo($value),
            FieldType::ListOfText => $this->listOfText($value),
        };
    }

    /**
     * @param array<string, mixed> $input chave do campo => valor bruto
     * @return array<string, mixed> chave do campo => valor canónico (só campos presentes no input)
     */
    public function sanitizeAll(CollectionPresetInterface $preset, array $input): array
    {
        $clean = [];
        foreach ($preset->fields() as $field) {
            if (array_key_exists($field->key, $input)) {
                $clean[$field->key] = $this->sanitize($field, $input[$field->key]);
            } elseif ($field->type === FieldType::Bool) {
                // Checkbox desmarcada não é enviada no POST.
                $clean[$field->key] = false;
            }
        }
        return $clean;
    }

    /**
     * Interpreta números escritos à mão: "285.000,00", "285,000.00", "285 000", "1250,5".
     * Com os dois separadores, o último é o decimal. Com um só, decide pelo separador decimal
     * da moeda e pelo padrão de milhares (grupos de 3 dígitos).
     */
    public static function parseNumber(mixed $value, string $decimal = ','): ?float
    {
        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? (float) $value : null;
        }
        if (! is_string($value)) {
            return null;
        }

        $s = trim($value);
        // Remove moeda, unidades e espaços (inclui espaço não separável).
        $s = (string) preg_replace('/[^\d,.\-]/u', '', $s);
        if ($s === '' || $s === '-' ) {
            return null;
        }

        $decimal = $decimal === '.' ? '.' : ',';
        $other = $decimal === ',' ? '.' : ',';
        $lastComma = strrpos($s, ',');
        $lastDot = strrpos($s, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $dec = $lastComma > $lastDot ? ',' : '.';
            $s = str_replace($dec === ',' ? '.' : ',', '', $s);
            $s = str_replace($dec, '.', $s);
        } elseif (str_contains($s, $decimal)) {
            $s = substr_count($s, $decimal) > 1 ? str_replace($decimal, '', $s) : str_replace($decimal, '.', $s);
        } elseif (str_contains($s, $other)) {
            $isThousands = substr_count($s, $other) > 1 || preg_match('/^-?\d{1,3}' . preg_quote($other, '/') . '\d{3}$/', $s) === 1;
            $s = $isThousands ? str_replace($other, '', $s) : str_replace($other, '.', $s);
        }

        if (! is_numeric($s)) {
            return null;
        }
        return (float) $s;
    }

    private function text(mixed $value, ?int $maxLength): string
    {
        $text = is_scalar($value) ? sanitize_text_field((string) $value) : '';
        return $maxLength !== null ? mb_substr($text, 0, $maxLength) : $text;
    }

    private function textarea(mixed $value, ?int $maxLength): string
    {
        $text = is_scalar($value) ? sanitize_textarea_field((string) $value) : '';
        return $maxLength !== null ? mb_substr($text, 0, $maxLength) : $text;
    }

    private function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'sim', 'on'], true);
        }
        return (bool) $value;
    }

    private function select(FieldDefinition $field, mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $normalized = FieldDefinition::normalizeOptionValue((string) $value);
        return array_key_exists($normalized, $field->options) ? $normalized : '';
    }

    /** @return list<string> */
    private function multiselect(FieldDefinition $field, mixed $value): array
    {
        $values = is_array($value) ? $value : (is_string($value) && $value !== '' ? explode(',', $value) : []);
        $clean = [];
        foreach ($values as $item) {
            if (! is_scalar($item)) {
                continue;
            }
            $normalized = FieldDefinition::normalizeOptionValue((string) $item);
            if (array_key_exists($normalized, $field->options) && ! in_array($normalized, $clean, true)) {
                $clean[] = $normalized;
            }
        }
        return $clean;
    }

    private function date(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }
        $value = trim($value);
        // Aceita AAAA-MM-DD e DD/MM/AAAA; guarda sempre AAAA-MM-DD (ordenável).
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) === 1) {
            [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m) === 1) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return '';
        }
        return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : '';
    }

    private function time(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }
        if (preg_match('/^(\d{1,2})[:hH](\d{2})?$/', trim($value), $m) !== 1) {
            return '';
        }
        $h = (int) $m[1];
        $min = (int) ($m[2] ?? 0);
        return ($h <= 23 && $min <= 59) ? sprintf('%02d:%02d', $h, $min) : '';
    }

    /** @return list<int> */
    private function gallery(mixed $value): array
    {
        $values = is_array($value) ? $value : (is_string($value) && $value !== '' ? explode(',', $value) : []);
        $ids = [];
        foreach ($values as $item) {
            $id = is_scalar($item) ? absint($item) : 0;
            if ($id > 0 && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    /** @return array{lat?: float, lng?: float} */
    private function geo(mixed $value): array
    {
        if (is_string($value) && str_contains($value, ',')) {
            [$lat, $lng] = array_map('trim', explode(',', $value, 2));
            $value = ['lat' => $lat, 'lng' => $lng];
        }
        if (! is_array($value)) {
            return [];
        }
        $lat = isset($value['lat']) && is_numeric($value['lat']) ? (float) $value['lat'] : null;
        $lng = isset($value['lng']) && is_numeric($value['lng']) ? (float) $value['lng'] : null;
        if ($lat === null || $lng === null || abs($lat) > 90 || abs($lng) > 180) {
            return [];
        }
        return ['lat' => round($lat, 7), 'lng' => round($lng, 7)];
    }

    /** @return list<string> */
    private function listOfText(mixed $value): array
    {
        $values = is_array($value) ? $value : (is_string($value) ? preg_split('/\r\n|\r|\n/', $value) : []);
        $clean = [];
        foreach ($values ?: [] as $item) {
            $item = is_scalar($item) ? sanitize_text_field((string) $item) : '';
            if ($item !== '') {
                $clean[] = $item;
            }
        }
        return $clean;
    }
}
