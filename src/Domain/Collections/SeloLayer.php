<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\Collections;

/**
 * Selo de destaque (@since 3.6.0): três campos que entram em TODOS os presets, sem cada ficheiro os declarar.
 *
 *   selo      escolha entre textos prontos ("Esgotado", "Últimas unidades", "Oportunidade"…)
 *   selo_texto  texto livre, quando se escolhe "Outro texto"
 *   selo_ate  data: com "Só até uma data" mostra "Só até 30/10"; em qualquer selo, depois dela o selo some
 *
 * O selo não mexe na situação do item (um imóvel esgotado pode continuar visível, como "oportunidade perdida").
 * Um preset pode trocar a lista com settings.selo_options (valor => rótulo), declarar os seus próprios campos
 * "selo"/"selo_texto"/"selo_ate" ou desligar com settings.selo = false.
 */
final class SeloLayer
{
    public const KIND_UNTIL = 'ate';
    public const KIND_CUSTOM = 'personalizado';

    public const OPTIONS = [
        'esgotado' => 'Esgotado',
        'ultimas' => 'Últimas unidades',
        'oportunidade' => 'Oportunidade',
        'novidade' => 'Novidade',
        self::KIND_UNTIL => 'Só até uma data',
        self::KIND_CUSTOM => 'Outro texto',
    ];

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function apply(array $config): array
    {
        $settings = is_array($config['settings'] ?? null) ? $config['settings'] : [];
        $fields = is_array($config['fields'] ?? null) ? $config['fields'] : [];
        if (($settings['selo'] ?? true) === false || isset($fields['selo']) || $fields === []) {
            return $config;
        }

        $groups = is_array($config['groups'] ?? null) ? $config['groups'] : [];
        if ($groups === []) {
            // Mesmo grupo por omissão do CollectionPreset, para os campos existentes não ficarem órfãos.
            $groups = ['principal' => 'Dados principais'];
        }
        if (! isset($groups['selo'])) {
            // Entra logo antes de "Notas internas" (ou no fim); os outros grupos mantêm a ordem.
            $ordered = [];
            foreach ($groups as $key => $label) {
                if ($key === 'privado') {
                    $ordered['selo'] = 'Selo de destaque';
                }
                $ordered[$key] = $label;
            }
            $config['groups'] = $ordered + ['selo' => 'Selo de destaque'];
        }

        $options = is_array($settings['selo_options'] ?? null) && $settings['selo_options'] !== [] ? $settings['selo_options'] : self::OPTIONS;
        $fields['selo'] = [
            'type' => 'select', 'label' => 'Selo de destaque', 'group' => 'selo', 'options' => $options,
            'help' => 'Mostra uma faixa na foto do item, na lista e na página dele. Não muda a situação do item. Deixe em branco para não mostrar.',
        ];
        $fields['selo_texto'] = [
            'type' => 'text', 'label' => 'Texto do selo', 'group' => 'selo', 'max_length' => 24,
            'placeholder' => 'Ex.: Condição especial', 'help' => 'Só vale quando o selo é "Outro texto".',
        ];
        $fields['selo_ate'] = [
            'type' => 'date', 'label' => 'Mostrar o selo até', 'group' => 'selo',
            'help' => 'Obrigatório em "Só até uma data". Nos outros, o selo some sozinho depois desse dia.',
        ];
        $config['fields'] = $fields;
        return $config;
    }
}
