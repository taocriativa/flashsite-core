<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Output\Elementor;

use FlashSite\Core\Domain\Collections\CollectionPresetInterface;
use FlashSite\Core\Domain\Collections\FieldFormatter;

require_once __DIR__ . '/ItemFieldTag.php';

/*
 * Tags de atalho com o campo fixo: escolhem-se pelo nome no Elementor (V3 e Atomic)
 * e não exigem configurar o controlo "Campo".
 *
 * @since 2.6.0
 */

/** "285.000,00 €", "350,00 €/mês" ou "Preço sob consulta". */
class ItemPriceTag extends ItemFieldTag
{
    protected function slug(): string { return 'flashsite-item-price'; }
    protected function title(): string { return 'Item · Preço'; }
    protected function fixedField(): ?string { return 'preco'; }

    protected function resolve(CollectionPresetInterface $preset, int $postId, FieldFormatter $formatter): string
    {
        $style = (string) ($this->get_settings('money') ?: FieldFormatter::MONEY_CENTS);
        return $formatter->price($preset, $postId, $style);
    }
}

/** "92 m²" (área útil). */
class ItemAreaTag extends ItemFieldTag
{
    protected function slug(): string { return 'flashsite-item-area'; }
    protected function title(): string { return 'Item · Área útil'; }
    protected function fixedField(): ?string { return 'area_util'; }
}

/** Nome dos termos de uma taxonomia do item (estado, tipologia, zona, categoria…). */
class ItemTermsTag extends ItemFieldTag
{
    protected function slug(): string { return 'flashsite-item-terms'; }
    protected function title(): string { return 'Item · Classificação'; }
    protected function fixedTaxonomy(): ?string { return null; }
    protected function fixedField(): ?string { return '__terms'; }

    protected function register_controls(): void
    {
        $this->registerItemSourceControls();
        if ($this->fixedTaxonomy() === null) {
            $this->add_control('taxonomy', [
                'label' => 'Classificação',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => self::taxonomyOptions(),
                'default' => '',
            ]);
        }
        $this->add_control('separator', ['label' => 'Separador', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => ', ']);
        $this->add_control('before', ['label' => 'Antes', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('after', ['label' => 'Depois', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('fallback', ['label' => 'Se estiver vazio', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
    }

    protected function resolve(CollectionPresetInterface $preset, int $postId, FieldFormatter $formatter): string
    {
        $taxonomy = $this->fixedTaxonomy() ?? (string) ($this->get_settings('taxonomy') ?? '');
        $separator = (string) ($this->get_settings('separator') ?? ', ');
        return $formatter->terms($preset, $taxonomy, $postId, $separator !== '' ? $separator : ', ');
    }
}

/** "Disponível", "Reservado", "Vendido"… */
class ItemStatusTag extends ItemTermsTag
{
    protected function slug(): string { return 'flashsite-item-status'; }
    protected function title(): string { return 'Item · Estado'; }
    protected function fixedTaxonomy(): ?string { return 'estado'; }
}

/** "T2". */
class ItemTypologyTag extends ItemTermsTag
{
    protected function slug(): string { return 'flashsite-item-typology'; }
    protected function title(): string { return 'Item · Tipologia'; }
    protected function fixedTaxonomy(): ?string { return 'tipologia'; }
}

/** "Alvalade, Lisboa". */
class ItemZoneTag extends ItemTermsTag
{
    protected function slug(): string { return 'flashsite-item-zone'; }
    protected function title(): string { return 'Item · Zona'; }
    protected function fixedTaxonomy(): ?string { return 'zona'; }
}

/** Título do item (para cartões de destaque montados sem loop). */
class ItemTitleTag extends ItemFieldTag
{
    protected function slug(): string { return 'flashsite-item-title'; }
    protected function title(): string { return 'Item · Título'; }
    protected function fixedField(): ?string { return '__title'; }

    protected function register_controls(): void
    {
        $this->registerItemSourceControls();
        $this->add_control('before', ['label' => 'Antes', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('after', ['label' => 'Depois', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('fallback', ['label' => 'Se estiver vazio', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
    }

    protected function resolve(CollectionPresetInterface $preset, int $postId, FieldFormatter $formatter): string
    {
        return get_post_status($postId) === 'publish' || is_preview() ? (string) get_the_title($postId) : '';
    }
}

/** Descrição do item (conteúdo do editor), em texto simples com parágrafos separados por linha. */
class ItemContentTag extends ItemTitleTag
{
    protected function slug(): string { return 'flashsite-item-content'; }
    protected function title(): string { return 'Item · Descrição'; }
    protected function fixedField(): ?string { return '__content'; }

    protected function resolve(CollectionPresetInterface $preset, int $postId, FieldFormatter $formatter): string
    {
        $post = get_post($postId);
        if (! $post instanceof \WP_Post) {
            return '';
        }
        $text = wp_strip_all_tags(strip_shortcodes((string) $post->post_content));
        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }

    public function render(): void
    {
        echo nl2br(esc_html($this->text()));
    }
}
