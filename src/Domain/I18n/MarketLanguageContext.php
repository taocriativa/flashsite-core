<?php
declare(strict_types=1);

namespace FlashSite\Core\Domain\I18n;

/**
 * Contexto por omissão (@since 3.2.0): um só idioma, o do mercado do site (PT → pt-PT, BR → pt-BR).
 * É o comportamento de sempre; nada muda em sites sem plugin de tradução.
 */
final class MarketLanguageContext implements LanguageContextInterface
{
    /** @param \Closure(): string $market devolve PT ou BR */
    public function __construct(private \Closure $market) {}

    public function current(): string
    {
        return $this->primary();
    }

    public function primary(): string
    {
        return Language::fromMarket((string) ($this->market)());
    }

    public function available(): array
    {
        return [$this->primary()];
    }

    public function ofPost(int $postId): string
    {
        return $this->primary();
    }
}
