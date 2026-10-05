<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Valida valores já sanitizados de um item contra a definição do preset.
 *
 * Regras transversais do preset (settings.rules):
 *  - ['required_unless' => ['preco', 'preco_sob_consulta']]  preço obrigatório salvo "sob consulta".
 *  - ['after_or_equal' => ['hora_fim', 'hora_inicio']]        hora/data final >= inicial.
 *
 * @since 2.6.0
 */
final class ItemValidator
{
    /**
     * @param array<string, mixed> $values chave => valor canónico
     * @return array<string, string> chave => mensagem em PT
     */
    public function validate(CollectionPresetInterface $preset, array $values): array
    {
        $errors = [];

        foreach ($preset->fields() as $field) {
            $value = $values[$field->key] ?? $field->type->emptyValue();

            if ($field->required && self::isEmpty($field, $value)) {
                $errors[$field->key] = sprintf('O campo "%s" é obrigatório.', $field->label);
                continue;
            }
            if (self::isEmpty($field, $value)) {
                continue;
            }

            if (in_array($field->type, [FieldType::Number, FieldType::Money], true) && is_float($value)) {
                if ($field->min !== null && $value < $field->min) {
                    $errors[$field->key] = sprintf('O campo "%s" não pode ser inferior a %s.', $field->label, self::num($field->min));
                } elseif ($field->max !== null && $value > $field->max) {
                    $errors[$field->key] = sprintf('O campo "%s" não pode ser superior a %s.', $field->label, self::num($field->max));
                }
            }
        }

        foreach ((array) $preset->setting('rules', []) as $rule) {
            if (! is_array($rule)) {
                continue;
            }
            if (isset($rule['required_unless']) && is_array($rule['required_unless'])) {
                [$target, $flag] = array_pad(array_values($rule['required_unless']), 2, '');
                $targetField = $preset->field((string) $target);
                $flagValue = (bool) ($values[(string) $flag] ?? false);
                if ($targetField !== null && ! $flagValue && self::isEmpty($targetField, $values[$targetField->key] ?? null) && ! isset($errors[$targetField->key])) {
                    $flagField = $preset->field((string) $flag);
                    $errors[$targetField->key] = sprintf(
                        'Indique "%s" ou marque "%s".',
                        $targetField->label,
                        $flagField?->label ?? (string) $flag
                    );
                }
            }
            if (isset($rule['after_or_equal']) && is_array($rule['after_or_equal'])) {
                [$later, $earlier] = array_pad(array_values($rule['after_or_equal']), 2, '');
                $a = (string) ($values[(string) $later] ?? '');
                $b = (string) ($values[(string) $earlier] ?? '');
                $laterField = $preset->field((string) $later);
                $earlierField = $preset->field((string) $earlier);
                if ($a !== '' && $b !== '' && $a < $b && $laterField !== null && $earlierField !== null && ! isset($errors[$laterField->key])) {
                    $errors[$laterField->key] = sprintf('"%s" não pode ser anterior a "%s".', $laterField->label, $earlierField->label);
                }
            }
        }

        return $errors;
    }

    public static function isEmpty(FieldDefinition $field, mixed $value): bool
    {
        return match ($field->type) {
            FieldType::Bool => $value !== true,
            FieldType::Number, FieldType::Money => $value === null || $value === '',
            FieldType::Image, FieldType::File => (int) $value <= 0,
            default => $value === null || $value === '' || $value === [],
        };
    }

    private static function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }
}
