<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\LanguageContextInterface;
use FlashSite\Core\Domain\I18n\MarketLanguageContext;
use FlashSite\Core\Domain\I18n\Strings;
use FlashSite\Core\Domain\Collections\FieldFormatter;
use FlashSite\Core\Modules\Multilingual\PolylangLanguageContext;

if (! function_exists('pll_current_language')) {
    function pll_current_language(string $field = 'slug'): string { return (string) ($GLOBALS['fs_test_pll']['current'] ?? ''); }
    function pll_default_language(string $field = 'slug'): string { return (string) ($GLOBALS['fs_test_pll']['default'] ?? ''); }
    function pll_languages_list(array $args = []): array { return (array) ($GLOBALS['fs_test_pll']['list'] ?? []); }
}

final class FixedLanguageContext implements LanguageContextInterface
{
    public function __construct(private string $code) {}
    public function current(): string { return $this->code; }
    public function primary(): string { return Language::PT_PT; }
    public function available(): array { return [Language::PT_PT, $this->code]; }
}

final class I18nTest extends TestCase
{
    public function run(): void
    {
        flashsite_reset_test_state();
        Strings::useDirectory(null);

        // Códigos a partir do locale.
        $this->assertSame('en', Language::fromLocale('en_GB'));
        $this->assertSame('en', Language::fromLocale('en-US'));
        $this->assertSame('pt-BR', Language::fromLocale('pt_BR'));
        $this->assertSame('pt-PT', Language::fromLocale('pt_PT'));
        $this->assertSame('fr', Language::fromLocale('fr_FR'));
        $this->assertSame('es', Language::fromLocale('es'));
        $this->assertSame('', Language::fromLocale('de_DE'), 'Idioma sem pacote: vazio.');
        $this->assertSame('pt-BR', Language::fromMarket('BR'));

        // Sem contexto: português, texto intacto.
        Strings::useContext(null);
        $this->assertSame('Ver todos', Strings::t('Ver todos'));

        // Mercado Brasil: pt-BR, texto intacto (sites atuais não mudam).
        Strings::useContext(new MarketLanguageContext(static fn (): string => 'BR'));
        $this->assertSame('pt-BR', Strings::language());
        $this->assertSame('/mês', Strings::t('/mês'));
        $this->assertSame('Preço sob consulta', Strings::t(FieldFormatter::ON_REQUEST_TEXT));

        // Inglês, espanhol, francês.
        Strings::useContext(new FixedLanguageContext('en'));
        $this->assertSame('See all', Strings::t('Ver todos'));
        $this->assertSame('from ', Strings::t('a partir de '), 'Espaço no fim preservado.');
        $this->assertSame(' /month', Strings::t(' /mês'), 'Espaço no início preservado.');
        $this->assertSame('Price on request', Strings::t(FieldFormatter::ON_REQUEST_TEXT));
        $this->assertSame('Texto sem tradução', Strings::t('Texto sem tradução'), 'Sem tradução: devolve o original.');
        $this->assertSame('/mois', Strings::t('/mês', 'fr'));
        $this->assertSame('Precio rebajado', Strings::t('Preço reduzido', 'es'));

        // Pacote incompleto cai no inglês.
        $dir = sys_get_temp_dir() . '/fs-i18n-' . getmypid();
        @mkdir($dir);
        file_put_contents($dir . '/en.php', "<?php return ['phrases' => ['Ver todos' => 'See all', 'Filtrar' => 'Filter']];");
        file_put_contents($dir . '/fr.php', "<?php return ['phrases' => ['Ver todos' => 'Voir tout']];");
        Strings::useDirectory($dir);
        $this->assertSame('Voir tout', Strings::t('Ver todos', 'fr'));
        $this->assertSame('Filter', Strings::t('Filtrar', 'fr'), 'Falta no fr: usa o en.');
        Strings::useDirectory(null);

        // Os pacotes es e fr têm as mesmas frases que o en.
        $root = dirname(__DIR__, 2) . '/config/i18n';
        $en = array_keys((require $root . '/en.php')['phrases']);
        foreach (['es', 'fr'] as $code) {
            $keys = array_keys((require $root . '/' . $code . '.php')['phrases']);
            $this->assertSame([], array_values(array_diff($en, $keys)), "Pacote $code sem frases do en.");
            $this->assertSame([], array_values(array_diff($keys, $en)), "Pacote $code com frases a mais.");
        }

        // Polylang: idioma da página, principal e lista.
        $market = new MarketLanguageContext(static fn (): string => 'PT');
        $GLOBALS['fs_test_pll'] = ['current' => 'en_GB', 'default' => 'pt_PT', 'list' => ['pt_PT', 'en_GB', 'fr_FR', 'de_DE']];
        $pll = new PolylangLanguageContext($market);
        $this->assertSame('en', $pll->current());
        $this->assertSame('pt-PT', $pll->primary());
        $this->assertSame(['pt-PT', 'en', 'fr'], $pll->available(), 'Idioma sem pacote fica de fora.');
        $GLOBALS['fs_test_pll']['current'] = 'de_DE';
        $this->assertSame('pt-PT', $pll->current(), 'Idioma desconhecido: principal.');
        $GLOBALS['fs_test_pll'] = ['current' => 'pt', 'default' => 'pt', 'list' => ['pt']];
        $br = new PolylangLanguageContext(new MarketLanguageContext(static fn (): string => 'BR'));
        $this->assertSame('pt-BR', $br->current(), 'Português sem região segue o mercado.');
        unset($GLOBALS['fs_test_pll']);
        Strings::useContext(null);
    }
}
