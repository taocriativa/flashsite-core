<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Business;

use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\Strings;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;

/**
 * Dados do negócio por idioma (@since 3.2.0-beta.5).
 *
 * Só os campos de texto mudam de idioma (frase curta, título, especialidade, horários e a
 * observação dos horários). Nome, contactos, morada e redes são os mesmos em todos os idiomas.
 * O idioma principal continua nos Dados do negócio de sempre; aqui ficam só os outros:
 * ['en' => ['hours.notes' => '…'], 'fr' => […]]. Campo vazio volta ao valor do idioma principal.
 */
final class BusinessTranslations
{
    public const OPTION = 'flashsite_business_translations';

    /** @var array<string, string> caminho => nome do campo no painel */
    public const PATHS = [
        'identity.tagline' => 'Frase curta',
        'professional.title' => 'Título profissional',
        'professional.specialty' => 'Especialidade',
        'hours.notes' => 'Observação dos horários',
        'hours.monday' => 'Segunda-feira',
        'hours.tuesday' => 'Terça-feira',
        'hours.wednesday' => 'Quarta-feira',
        'hours.thursday' => 'Quinta-feira',
        'hours.friday' => 'Sexta-feira',
        'hours.saturday' => 'Sábado',
        'hours.sunday' => 'Domingo',
    ];

    public function __construct(private OptionsStorage $storage) {}

    public static function isTranslatable(string $path): bool
    {
        return isset(self::PATHS[$path]);
    }

    /** @return array<string, array<string, string>> */
    public function all(): array
    {
        $raw = $this->storage->get(self::OPTION, []);
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $language => $values) {
            if (! Language::isSupported((string) $language) || ! is_array($values)) {
                continue;
            }
            foreach ($values as $path => $value) {
                if (self::isTranslatable((string) $path) && is_scalar($value) && trim((string) $value) !== '') {
                    $out[(string) $language][(string) $path] = (string) $value;
                }
            }
        }
        return $out;
    }

    /** Valor do idioma pedido; '' quando não há tradução (quem chama usa o do idioma principal). */
    public function get(string $path, string $language): string
    {
        return $this->all()[$language][$path] ?? '';
    }

    /**
     * Valor a mostrar: no idioma principal, sempre o original; nos outros, a tradução quando existe.
     */
    public function resolve(string $path, mixed $original, ?string $language = null): mixed
    {
        if (! self::isTranslatable($path)) {
            return $original;
        }
        $language ??= Strings::language();
        $context = Strings::context();
        $primary = $context !== null ? $context->primary() : Language::PT_PT;
        if ($language === $primary) {
            return $original;
        }
        $translated = $this->get($path, $language);
        return $translated !== '' ? $translated : $original;
    }

    /**
     * Guarda as traduções enviadas (substitui os idiomas enviados; os outros ficam).
     *
     * @param array<string, mixed> $input idioma => [caminho => texto]
     */
    public function save(array $input): bool
    {
        $all = $this->all();
        foreach ($input as $language => $values) {
            $language = (string) $language;
            if (! Language::isSupported($language) || ! is_array($values)) {
                continue;
            }
            $clean = [];
            foreach ($values as $path => $value) {
                $path = (string) $path;
                if (! self::isTranslatable($path) || ! is_scalar($value)) {
                    continue;
                }
                $value = trim(str_replace(["\r", "\t"], ['', ' '], (function_exists('wp_strip_all_tags') ? wp_strip_all_tags((string) $value) : strip_tags((string) $value))));
                if ($value !== '') {
                    $clean[$path] = $value;
                }
            }
            if ($clean === []) {
                unset($all[$language]);
            } else {
                $all[$language] = $clean;
            }
        }
        return $this->storage->update(self::OPTION, $all, true);
    }
}
