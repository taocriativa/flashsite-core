<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Business\BusinessTranslations;
use FlashSite\Core\Domain\Collections\Currency;
use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\LanguageContextInterface;
use FlashSite\Core\Domain\I18n\Strings;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\Multilingual\LanguageSwitcher;

/** 3.2.0-beta.5: dados do negócio por idioma, preço no formato do idioma, seletor fs-idiomas. */
final class MultilingualBeta5Test extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();
        $context = new class implements LanguageContextInterface {
            public string $now = Language::PT_PT;
            public function current(): string { return $this->now; }
            public function primary(): string { return Language::PT_PT; }
            public function available(): array { return [Language::PT_PT, Language::EN, Language::FR]; }
            public function ofPost(int $postId): string { return Language::PT_PT; }
        };
        Strings::useContext($context);

        // Dados do negócio: só campos de texto; vazio volta ao principal.
        $t = new BusinessTranslations(new OptionsStorage());
        $t->save(['en' => ['hours.notes' => ' Lesson times follow the tide. ', 'contact.phone' => '999', 'hours.monday' => ''], 'xx' => ['hours.notes' => 'x']]);
        $this->assertSame(['en' => ['hours.notes' => 'Lesson times follow the tide.']], $t->all());
        $this->assertSame('Horário pela maré.', $t->resolve('hours.notes', 'Horário pela maré.'));
        $context->now = Language::EN;
        $this->assertSame('Lesson times follow the tide.', $t->resolve('hours.notes', 'Horário pela maré.'));
        $this->assertSame('8h–19h', $t->resolve('hours.monday', '8h–19h'), 'Sem tradução: valor do idioma principal.');
        $this->assertSame('+351 915', $t->resolve('contact.phone', '+351 915'), 'Contactos não mudam de idioma.');
        $context->now = Language::FR;
        $this->assertSame('Horário pela maré.', $t->resolve('hours.notes', 'Horário pela maré.'));

        // Preço: inglês lê "€45.00"; francês e português ficam "45,00 €"; o painel (number) não muda.
        $eur = Currency::of('EUR');
        $context->now = Language::EN;
        $this->assertSame('€45.00', $eur->format(45, 2));
        $this->assertSame('€1,250', $eur->format(1250, 0));
        $this->assertSame('45,00', $eur->number(45, 2));
        $this->assertSame('R$1,250.00', Currency::of('BRL')->format(1250, 2));
        $context->now = Language::FR;
        $this->assertSame('45,00 €', $eur->format(45, 2));
        $context->now = Language::PT_PT;
        $this->assertSame('45,00 €', $eur->format(45, 2));
        Strings::useContext(null);

        // Seletor: links para as traduções, idioma atual marcado.
        $html = '<p class="e-1 fs-idiomas e-paragraph-base">PT EN</p>';
        $out = LanguageSwitcher::markup($html, [
            ['slug' => 'pt', 'url' => 'https://x.pt/', 'name' => 'Português', 'locale' => 'pt_PT', 'current_lang' => true],
            ['slug' => 'en', 'url' => 'https://x.pt/en/home/', 'name' => 'English', 'locale' => 'en_GB', 'current_lang' => false],
        ]);
        $this->assertSame(true, str_contains($out, '<a class="fs-idiomas__item is-ativo" href="https://x.pt/" hreflang="pt-PT" lang="pt-PT" title="Português" aria-current="true">PT</a>'));
        $this->assertSame(true, str_contains($out, 'href="https://x.pt/en/home/" hreflang="en-GB"'));
        $this->assertSame(false, str_contains($out, 'PT EN'));
        $this->assertSame($html, LanguageSwitcher::markup($html, []), 'Sem idiomas: não mexe.');
    }
}
