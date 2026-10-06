<?php
declare(strict_types=1);

namespace FlashSite\Core\Modules\Collections\Output\Elementor;

use FlashSite\Core\Domain\Collections\FieldType;

require_once __DIR__ . '/CollectionTagBase.php';

/*
 * Tags de imagem, galeria e link do item atual.
 *
 * @since 2.6.0
 */

/**
 * Imagem do item: capa, N.ª foto da galeria ou um campo de imagem (ex.: planta).
 * Settings: source ("cover" | "gallery" | chave de um campo image), index (1..n para gallery).
 */
class ItemImageTag extends CollectionTagBase
{
    protected function slug(): string { return 'flashsite-item-image'; }
    protected function title(): string { return 'Item · Imagem'; }

    public function get_categories(): array
    {
        return [\Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY, \Elementor\Modules\DynamicTags\Module::MEDIA_CATEGORY];
    }

    protected function register_controls(): void
    {
        $this->registerItemSourceControls();
        $this->add_control('source', [
            'label' => 'Imagem',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => ['cover' => 'Capa', 'gallery' => 'Foto da galeria'] + self::fieldOptions([FieldType::Image]),
            'default' => 'cover',
        ]);
        $this->add_control('index', [
            'label' => 'N.º da foto',
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 1,
            'min' => 1,
            'condition' => ['source' => 'gallery'],
        ]);
    }

    /** @return array{id: int, url: string} */
    public function get_value(array $options = []): array
    {
        $id = $this->attachmentId();
        $url = $id > 0 ? (string) wp_get_attachment_image_url($id, 'full') : '';
        return $url !== '' ? ['id' => $id, 'url' => $url] : ['id' => 0, 'url' => ''];
    }

    public function render(): void
    {
        echo esc_url($this->get_value()['url']);
    }

    private function attachmentId(): int
    {
        $postId = $this->currentPostId();
        $preset = $this->currentPreset($postId);
        $reader = self::reader();
        if ($preset === null || $reader === null) {
            return 0;
        }
        $source = (string) ($this->get_settings('source') ?: 'cover');
        if ($source === 'cover') {
            return (int) get_post_thumbnail_id($postId);
        }
        if ($source === 'gallery') {
            foreach ($preset->publicFields() as $field) {
                if ($field->type === FieldType::Gallery) {
                    $ids = (array) $reader->value($field, $postId);
                    $index = max(1, (int) ($this->get_settings('index') ?: 1)) - 1;
                    return (int) ($ids[$index] ?? 0);
                }
            }
            return 0;
        }
        $field = $preset->field($source);
        return $field !== null && $field->public && $field->type === FieldType::Image ? (int) $reader->value($field, $postId) : 0;
    }
}

/** Galeria completa do item (widgets Galeria/Carrossel do Elementor). */
class ItemGalleryTag extends CollectionTagBase
{
    protected function slug(): string { return 'flashsite-item-gallery'; }
    protected function title(): string { return 'Item · Galeria'; }

    public function get_categories(): array
    {
        return [\Elementor\Modules\DynamicTags\Module::GALLERY_CATEGORY];
    }

    protected function register_controls(): void
    {
        $this->registerItemSourceControls();
    }

    /** @return list<array{id: int, url: string}> */
    public function get_value(array $options = []): array
    {
        $postId = $this->currentPostId();
        $preset = $this->currentPreset($postId);
        $reader = self::reader();
        if ($preset === null || $reader === null) {
            return [];
        }
        foreach ($preset->publicFields() as $field) {
            if ($field->type !== FieldType::Gallery) {
                continue;
            }
            $images = [];
            foreach ((array) $reader->value($field, $postId) as $id) {
                $url = (string) wp_get_attachment_image_url((int) $id, 'full');
                if ($url !== '') {
                    $images[] = ['id' => (int) $id, 'url' => $url];
                }
            }
            return $images;
        }
        return [];
    }

    public function render(): void
    {
        echo esc_html((string) count($this->get_value()));
    }
}

/** Link de um campo URL do item (vídeo, visita virtual, bilhetes, reserva). */
class ItemUrlTag extends CollectionTagBase
{
    protected function slug(): string { return 'flashsite-item-url'; }
    protected function title(): string { return 'Item · Link'; }

    public function get_categories(): array
    {
        return [\Elementor\Modules\DynamicTags\Module::URL_CATEGORY];
    }

    protected function register_controls(): void
    {
        $this->registerItemSourceControls();
        $this->add_control('field', [
            'label' => 'Campo',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => ['__permalink' => 'Página do item'] + self::fieldOptions([FieldType::Url]),
            'default' => '__permalink',
        ]);
    }

    public function get_value(array $options = []): string
    {
        $postId = $this->currentPostId();
        $preset = $this->currentPreset($postId);
        $reader = self::reader();
        if ($preset === null || $reader === null) {
            return '';
        }
        $key = (string) ($this->get_settings('field') ?? '');
        // Sem campo escolhido, o link vai para a página do item (o caso mais comum).
        if ($key === '' || $key === '__permalink') {
            return get_post_status($postId) === 'publish' ? (string) get_permalink($postId) : '';
        }
        $field = $preset->field($key);
        if ($field === null || ! $field->public || $field->type !== FieldType::Url) {
            return '';
        }
        return (string) $reader->value($field, $postId);
    }

    public function render(): void
    {
        echo esc_url($this->get_value());
    }
}
