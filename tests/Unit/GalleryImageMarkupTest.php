<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Modules\Collections\Output\CollectionsOutput;

/** Miniaturas da galeria (@since 3.1.0): lazy e "sizes" nas fotos 2..n; capa e 1.ª foto intactas. */
final class GalleryImageMarkupTest extends TestCase
{
    public function run(): void
    {
        $img = '<img class="e-image-base" src="a-768x1024.webp" srcset="a-768x1024.webp 768w, a.webp 1080w" alt="x"/>';
        $tag = static fn (string $source, int $index): array => ['image' => ['value' => ['src' => ['$$type' => 'dynamic', 'value' => [
            'name' => 'flashsite-item-image',
            'settings' => ['source' => ['$$type' => 'string', 'value' => $source], 'index' => ['$$type' => 'number', 'value' => $index]],
        ]]]]];

        $thumb = CollectionsOutput::galleryImageMarkup($img, $tag('gallery', 2));
        $this->assertTrue(strpos($thumb, 'loading="lazy"') !== false);
        $this->assertTrue(strpos($thumb, 'sizes="auto, (max-width: 767px) 50vw, 25vw"') !== false);

        $this->assertSame($img, CollectionsOutput::galleryImageMarkup($img, $tag('gallery', 1)));
        $this->assertSame($img, CollectionsOutput::galleryImageMarkup($img, $tag('cover', 1)));
        $this->assertSame($img, CollectionsOutput::galleryImageMarkup($img, ['image' => ['value' => ['src' => ['$$type' => 'image-src']]]]));

        // Atributos já definidos não são duplicados.
        $own = '<img loading="eager" sizes="100vw" srcset="a 1w" src="a"/>';
        $this->assertSame($own, CollectionsOutput::galleryImageMarkup($own, $tag('gallery', 3)));
    }
}
