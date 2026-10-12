<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\SiteTexts;

use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\Strings;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

/**
 * Textos do site editáveis pelo cliente (@since 3.1.0).
 *
 * Quem monta o site marca um texto com a tag do Elementor "Texto do site" (chave, rótulo, tipo,
 * texto padrão). O Core lê as páginas, monta a lista de campos e o cliente edita os valores em
 * FlashSite › Textos do site, sem abrir o Elementor. Sem valor guardado, vale o texto padrão.
 *
 * Sites com vários idiomas (@since 3.2.0-beta.3): cada idioma tem a sua página, com as mesmas
 * chaves. O idioma principal guarda com a chave simples (como sempre, sem migração); os outros
 * guardam como "chave@idioma" (ex.: inicio_titulo@en). A página mostra o valor do seu idioma.
 */
final class SiteTextsRegistry
{
    public const TAG = 'flashsite-site-text';
    public const FIELDS_OPTION = 'flashsite_site_texts_fields';
    public const VALUES_OPTION = 'flashsite_site_texts_values';
    public const TYPES = ['line', 'paragraph'];
    public const LANGUAGE_SEPARATOR = '@';

    public function __construct(private OptionsStorage $storage) {}

    /**
     * Campos encontrados nos dados de uma página do Elementor (widgets atómicos e clássicos).
     *
     * @param array<mixed> $data _elementor_data decodificado
     * @return list<array{key: string, label: string, type: string, default: string, section: string, element: string}>
     */
    public static function extract(array $data): array
    {
        $found = [];
        self::walk($data, $found, '');
        return array_values($found);
    }

    /** @param array<string, array{key: string, label: string, type: string, default: string, section: string}> $found */
    private static function walk(mixed $node, array &$found, string $element): void
    {
        if (is_string($node)) {
            // Widgets clássicos: __dynamic__ = [elementor-tag id="…" name="flashsite-site-text" settings="%7B…%7D"]
            if (str_contains($node, 'name="' . self::TAG . '"') && preg_match('/settings="([^"]*)"/', $node, $m)) {
                $settings = json_decode(urldecode($m[1]), true);
                if (is_array($settings)) {
                    self::add($settings, $found, $element);
                }
            }
            return;
        }
        if (! is_array($node)) {
            return;
        }
        // Widgets atómicos: {"$$type": "dynamic", "value": {"name": "flashsite-site-text", "settings": {…}}}
        if (($node['$$type'] ?? '') === 'dynamic' && is_array($node['value'] ?? null) && ($node['value']['name'] ?? '') === self::TAG) {
            self::add((array) ($node['value']['settings'] ?? []), $found, $element);
            return;
        }
        // Elemento do Elementor: o id serve para o "Mostrar no site".
        if (isset($node['id']) && is_string($node['id']) && (isset($node['elType']) || isset($node['widgetType']))) {
            $element = $node['id'];
        }
        foreach ($node as $child) {
            self::walk($child, $found, $element);
        }
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, array{key: string, label: string, type: string, default: string, section: string}> $found
     */
    private static function add(array $settings, array &$found, string $element = ''): void
    {
        $get = static function (string $name) use ($settings): string {
            $value = $settings[$name] ?? '';
            if (is_array($value)) {
                $value = $value['value'] ?? '';
            }
            return is_scalar($value) ? trim((string) $value) : '';
        };
        $key = self::sanitizeKey($get('key'));
        if ($key === '' || isset($found[$key])) {
            return;
        }
        $type = $get('type');
        $found[$key] = [
            'key' => $key,
            'label' => $get('label') !== '' ? $get('label') : $key,
            'type' => in_array($type, self::TYPES, true) ? $type : 'line',
            'default' => $get('default'),
            'section' => $get('section'),
            'element' => $element,
            'max' => max(0, (int) $get('max')),
        ];
    }

    /** Folga acima do tamanho previsto antes de travar (20%). */
    public const LIMIT_MARGIN = 1.2;

    /**
     * Tamanho previsto de um campo e o limite que trava (@since 3.1.0-beta.9).
     * Sem "max" na tag, o previsto sai do texto padrão (o layout foi desenhado para ele) com 30% de folga.
     *
     * @param array<string, mixed> $field
     * @return array{max: int, hard: int}
     */
    public static function limit(array $field): array
    {
        $max = (int) ($field['max'] ?? 0);
        if ($max <= 0) {
            $paragraph = ($field['type'] ?? 'line') === 'paragraph';
            $length = function_exists('mb_strlen') ? mb_strlen((string) ($field['default'] ?? '')) : strlen((string) ($field['default'] ?? ''));
            $max = max($paragraph ? 80 : 20, (int) ceil($length * 1.3 / 5) * 5);
        }
        return ['max' => $max, 'hard' => (int) ceil($max * self::LIMIT_MARGIN)];
    }

    /**
     * Chaves cujo valor guardado passou do tamanho previsto (dentro da folga).
     *
     * @return list<string>
     */
    public function overLimit(): array
    {
        $fields = $this->fields();
        $over = [];
        foreach ($this->values() as $key => $value) {
            if (isset($fields[$key]) && mb_strlen($value) > self::limit($fields[$key])['max']) {
                $over[] = $key;
            }
        }
        return $over;
    }

    public static function sanitizeKey(string $key): string
    {
        $key = function_exists('remove_accents') ? remove_accents($key) : strtr($key, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c',
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'É' => 'E', 'Ê' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ú' => 'U', 'Ç' => 'C',
        ]);
        $key = strtolower($key);
        $key = (string) preg_replace('/[^a-z0-9_\-]+/', '_', $key);
        return trim($key, '_-');
    }

    /** Idioma principal do site (sem contexto: português de Portugal). */
    public static function primaryLanguage(): string
    {
        $context = Strings::context();
        return $context !== null ? $context->primary() : Language::PT_PT;
    }

    /** Onde o valor fica guardado: chave simples no idioma principal, "chave@idioma" nos outros. */
    public static function storageId(string $key, string $language = ''): string
    {
        $key = self::sanitizeKey($key);
        if ($language === '' || $language === self::primaryLanguage() || ! Language::isSupported($language)) {
            return $key;
        }
        return $key . self::LANGUAGE_SEPARATOR . $language;
    }

    /** "inicio_titulo@en" → chave e idioma limpos; idioma desconhecido devolve '' (nada é guardado). */
    public static function sanitizeId(string $id): string
    {
        $parts = explode(self::LANGUAGE_SEPARATOR, $id, 2);
        if (isset($parts[1]) && ! Language::isSupported(trim($parts[1]))) {
            return '';
        }
        return self::storageId($parts[0], isset($parts[1]) ? trim($parts[1]) : '');
    }

    /**
     * Idiomas com textos editáveis, o principal primeiro.
     *
     * @return list<string>
     */
    public function languages(): array
    {
        $primary = self::primaryLanguage();
        $found = [];
        foreach ($this->fields() as $field) {
            $found[] = (string) ($field['language'] ?? '') !== '' ? (string) $field['language'] : $primary;
        }
        $found = array_values(array_unique($found));
        $others = array_values(array_diff($found, [$primary]));
        return in_array($primary, $found, true) || $others === [] ? array_merge([$primary], $others) : $others;
    }

    /**
     * Campos de um idioma (vazio = principal). Campos de antes da 3.2 não têm idioma: são do principal.
     *
     * @return array<string, array<string, mixed>>
     */
    public function fieldsFor(string $language = ''): array
    {
        $primary = self::primaryLanguage();
        $language = $language !== '' ? $language : $primary;
        return array_filter($this->fields(), static function (array $field) use ($language, $primary): bool {
            $own = (string) ($field['language'] ?? '');
            return ($own !== '' ? $own : $primary) === $language;
        });
    }

    /**
     * Lista de campos guardada: chave → campo + páginas onde aparece.
     *
     * @return array<string, array{key: string, label: string, type: string, default: string, section: string, pages: list<int>}>
     */
    public function fields(): array
    {
        $fields = $this->storage->get(self::FIELDS_OPTION, []);
        return is_array($fields) ? $fields : [];
    }

    /**
     * Substitui os campos de uma página pelos encontrados agora. $language = idioma da página
     * (vazio = principal); nos outros idiomas o campo fica guardado como "chave@idioma".
     *
     * @param list<array{key: string, label: string, type: string, default: string, section: string}> $found
     */
    public function syncPage(int $pageId, array $found, string $language = ''): bool
    {
        $language = Language::isSupported($language) ? $language : self::primaryLanguage();
        $fields = $this->fields();
        foreach ($fields as $key => $field) {
            $pages = array_values(array_diff((array) ($field['pages'] ?? []), [$pageId]));
            if ($pages === []) {
                unset($fields[$key]);
            } else {
                $fields[$key]['pages'] = $pages;
            }
        }
        foreach ($found as $field) {
            $key = self::storageId($field['key'], $language);
            $field['id'] = $key;
            $field['language'] = $language;
            $pages = isset($fields[$key]) ? (array) $fields[$key]['pages'] : [];
            $pages[] = $pageId;
            $fields[$key] = $field + ['pages' => []];
            $fields[$key]['pages'] = array_values(array_unique(array_map('intval', $pages)));
        }
        return $this->storage->update(self::FIELDS_OPTION, $fields, false);
    }

    /** @return array<string, string> */
    public function values(): array
    {
        $values = $this->storage->get(self::VALUES_OPTION, []);
        return is_array($values) ? array_map('strval', $values) : [];
    }

    /** Texto a mostrar: o valor do cliente ou, sem ele, o padrão da tag. */
    public function text(string $key, string $default = '', ?string $language = null): string
    {
        $value = $this->values()[self::storageId($key, $language ?? Strings::language())] ?? '';
        return trim($value) !== '' ? $value : $default;
    }

    /**
     * Guarda os valores enviados pelo formulário (só chaves conhecidas). Texto vazio = volta ao padrão.
     *
     * @param array<string, mixed> $input
     * @return list<string> chaves que mudaram
     */
    public function saveValues(array $input): array
    {
        $fields = $this->fields();
        $values = $this->values();
        $changed = [];
        foreach ($input as $key => $raw) {
            $key = self::sanitizeId((string) $key);
            if (! isset($fields[$key]) || ! is_scalar($raw)) {
                continue;
            }
            $value = $fields[$key]['type'] === 'paragraph'
                ? self::cleanParagraph((string) $raw)
                : self::cleanLine((string) $raw);
            // Acima da folga o layout quebra: corta no limite (o campo já não deixa escrever mais).
            $hard = self::limit($fields[$key])['hard'];
            if (mb_strlen($value) > $hard) {
                $value = rtrim(mb_substr($value, 0, $hard));
            }
            // Igual ao padrão: não guarda (assim uma mudança do padrão continua a valer).
            if ($value === trim((string) $fields[$key]['default'])) {
                $value = '';
            }
            if (($values[$key] ?? '') !== $value) {
                $changed[] = $key;
            }
            if ($value === '') {
                unset($values[$key]);
            } else {
                $values[$key] = $value;
            }
        }
        $this->storage->update(self::VALUES_OPTION, $values, true);
        return $changed;
    }

    public static function cleanLine(string $value): string
    {
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        $value = function_exists('wp_strip_all_tags') ? wp_strip_all_tags($value) : strip_tags($value);
        return trim((string) preg_replace('/ {2,}/', ' ', $value));
    }

    public static function cleanParagraph(string $value): string
    {
        $value = str_replace("\r\n", "\n", $value);
        $value = function_exists('wp_strip_all_tags') ? wp_strip_all_tags($value, false) : strip_tags($value);
        $value = (string) preg_replace("/\n{3,}/", "\n\n", $value);
        return trim($value);
    }
}
