<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\I18n;

/**
 * De onde vem o idioma da página (@since 3.2.0). O Domain só conhece este contrato;
 * quem lê um plugin de tradução (ex.: Polylang) é um módulo adaptador.
 */
interface LanguageContextInterface
{
    /** Idioma da página atual (código de Language). */
    public function current(): string;

    /** Idioma principal do site. */
    public function primary(): string;

    /** @return list<string> idiomas ativos no site, o principal primeiro */
    public function available(): array;

    /** Idioma de uma página ou item (@since 3.2.0-beta.3); sem idioma próprio, o principal. */
    public function ofPost(int $postId): string;
}
