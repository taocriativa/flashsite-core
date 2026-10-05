<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Tipos de campo suportados pelos presets de Coleções.
 *
 * Cada tipo define como o valor é guardado em post meta (tipo WP) e o schema REST.
 *
 * @since 2.6.0
 */
enum FieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Richtext = 'richtext';
    case Number = 'number';
    case Money = 'money';
    case Bool = 'bool';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Date = 'date';
    case Time = 'time';
    case Url = 'url';
    case Email = 'email';
    case Tel = 'tel';
    case Image = 'image';
    case File = 'file';
    case Gallery = 'gallery';
    case Geo = 'geo';
    case ListOfText = 'list';

    /** Tipo usado em register_post_meta(). */
    public function metaType(): string
    {
        return match ($this) {
            self::Number, self::Money => 'number',
            self::Bool => 'boolean',
            self::Image, self::File => 'integer',
            self::Multiselect, self::Gallery, self::ListOfText => 'array',
            self::Geo => 'object',
            default => 'string',
        };
    }

    /**
     * Schema REST para tipos compostos (obrigatório pelo WP para array/object).
     *
     * @return array<string, mixed>
     */
    public function restSchema(): array
    {
        return match ($this) {
            self::Multiselect, self::ListOfText => ['type' => 'array', 'items' => ['type' => 'string']],
            self::Gallery => ['type' => 'array', 'items' => ['type' => 'integer']],
            self::Geo => [
                'type' => 'object',
                'properties' => [
                    'lat' => ['type' => 'number'],
                    'lng' => ['type' => 'number'],
                ],
                'additionalProperties' => false,
            ],
            default => ['type' => $this->metaType()],
        };
    }

    /** Valor vazio canónico do tipo. */
    public function emptyValue(): mixed
    {
        return match ($this) {
            self::Number, self::Money => null,
            self::Bool => false,
            self::Image, self::File => 0,
            self::Multiselect, self::Gallery, self::ListOfText => [],
            self::Geo => [],
            default => '',
        };
    }

    public function hasOptions(): bool
    {
        return $this === self::Select || $this === self::Multiselect;
    }
}
