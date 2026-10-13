<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Core\Logger;
use FlashSite\Core\Domain\Business\BusinessData;
use FlashSite\Core\Domain\Business\BusinessProfile;
use FlashSite\Core\Domain\Business\BusinessRepository;
use FlashSite\Core\Domain\Business\BusinessTranslations;
use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\LanguageContextInterface;
use FlashSite\Core\Domain\I18n\Strings;
use FlashSite\Core\Infrastructure\Storage\OptionsStorage;
use FlashSite\Core\Modules\OutputFoundation\OutputFoundationModule;

/** 3.3.0-beta.6: texto completo dos horários no idioma da página; português e API como sempre. */
final class MultilingualHoursTest extends TestCase
{
    private const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    private object $context;

    public function run(): void
    {
        $this->context = new class implements LanguageContextInterface {
            public string $now = Language::PT_PT;
            public function current(): string { return $this->now; }
            public function primary(): string { return Language::PT_PT; }
            public function available(): array { return [Language::PT_PT, Language::EN, Language::FR, Language::ES]; }
            public function ofPost(int $postId): string { return Language::PT_PT; }
        };
        Strings::useContext($this->context);

        $this->portugueseUnchanged();
        $this->allDaysEqual();
        $this->weekdaysAndWeekend();
        $this->translatedValuesDoNotGroupWithOriginal();
        $this->fallbackPerDay();
        $this->notes();
        $this->shortcodeStaysDaily();
        $this->publicApiStaysPortuguese();

        Strings::useContext(null);
    }

    /** @param array<string, string> $values */
    private function hours(array $values, string $notes = '', string $legacy = ''): array
    {
        return ['hours' => array_merge(array_fill_keys(self::DAYS, ''), $values, ['notes' => $notes, 'legacy_text' => $legacy])];
    }

    /** @param array<string, array<string, string>> $translations */
    private function setUp(array $profile, array $translations = []): array
    {
        flashsite_reset_test_state();
        $repo = new BusinessRepository(new OptionsStorage());
        $repo->saveProfile(new BusinessProfile($profile));
        $translator = new BusinessTranslations(new OptionsStorage());
        if ($translations !== []) {
            $translator->save($translations);
        }
        $module = new OutputFoundationModule(new BusinessData($repo), new Logger());
        $module->register();
        return [$repo, $translator, $module];
    }

    /** O que a tag "Horários (Texto Completo)" chama: perfil + idioma + resolvedor, com " · " entre grupos. */
    private function tag(BusinessRepository $repo, BusinessTranslations $translator, string $language): string
    {
        return str_replace("\n", ' · ', $repo->getProfile()->getOpeningHours($language, $translator->resolver($language)));
    }

    private function shortcode(OutputFoundationModule $module): string
    {
        return $module->renderGenericShortcode(['field' => 'business.hours.formatted', 'multiline' => 'yes']);
    }

    /** 1. Português byte a byte: saídas de antes da mudança, gravadas em tests/fixtures/hours_pt_baseline.json. */
    private function portugueseUnchanged(): void
    {
        $baseline = json_decode((string) file_get_contents(__DIR__ . '/../fixtures/hours_pt_baseline.json'), true);
        $this->assertTrue(is_array($baseline) && count($baseline['cases']) === 9, 'Fixture dos horários em PT.');
        foreach (['pt-PT' => Language::PT_PT, 'pt-BR' => Language::PT_BR] as $name => $language) {
            $this->context->now = $language;
            foreach ($baseline['cases'] as $case => $profile) {
                [$repo, $translator, $module] = $this->setUp($profile);
                $expected = $baseline['out'][$case];
                $this->assertSame($expected['grouped'], $repo->getProfile()->getOpeningHours(), "$name $case: getOpeningHours() sem argumentos");
                $this->assertSame($expected['grouped'], $repo->getProfile()->getOpeningHours($language, $translator->resolver($language)), "$name $case: com idioma e resolvedor");
                $this->assertSame($expected['lines'], $this->shortcode($module), "$name $case: shortcode");
            }
        }
        $this->context->now = Language::PT_PT;
    }

    /** 2. Todos os dias iguais. */
    private function allDaysEqual(): void
    {
        [$repo, $translator] = $this->setUp($this->hours(array_fill_keys(self::DAYS, '9h–18h')));
        $this->assertSame('Mon to Sun: 9h–18h', $this->tag($repo, $translator, Language::EN));
        $this->assertSame('Lun à Dim: 9h–18h', $this->tag($repo, $translator, Language::FR));
        $this->assertSame('Lun a Dom: 9h–18h', $this->tag($repo, $translator, Language::ES));
        $this->assertSame('Seg a Dom: 9h–18h', $this->tag($repo, $translator, Language::PT_PT));
    }

    /** 3. Dias úteis iguais e fim de semana igual. */
    private function weekdaysAndWeekend(): void
    {
        $profile = $this->hours(['monday' => '9h–18h', 'tuesday' => '9h–18h', 'wednesday' => '9h–18h', 'thursday' => '9h–18h', 'friday' => '9h–18h', 'saturday' => '10h–13h', 'sunday' => '10h–13h']);
        [$repo, $translator] = $this->setUp($profile);
        $this->assertSame('Mon to Fri: 9h–18h · Sat and Sun: 10h–13h', $this->tag($repo, $translator, Language::EN));
        $this->assertSame('Lun à Ven: 9h–18h · Sam et Dim: 10h–13h', $this->tag($repo, $translator, Language::FR));
        $this->assertSame('Lun a Vie: 9h–18h · Sáb y Dom: 10h–13h', $this->tag($repo, $translator, Language::ES));
        $this->assertSame('Seg a Sex: 9h–18h · Sáb e Dom: 10h–13h', $this->tag($repo, $translator, Language::PT_PT));

        // Traduzido igual nos dias úteis: continua a agrupar.
        [$repo, $translator] = $this->setUp($profile, ['en' => ['hours.monday' => '9am–6pm', 'hours.tuesday' => '9am–6pm', 'hours.wednesday' => '9am–6pm', 'hours.thursday' => '9am–6pm', 'hours.friday' => '9am–6pm', 'hours.saturday' => '10am–1pm', 'hours.sunday' => '10am–1pm']]);
        $this->assertSame('Mon to Fri: 9am–6pm · Sat and Sun: 10am–1pm', $this->tag($repo, $translator, Language::EN));
        $this->assertSame('Seg a Sex: 9h–18h · Sáb e Dom: 10h–13h', $this->tag($repo, $translator, Language::PT_PT), 'Português não vê a tradução.');
    }

    /** 4. Valores traduzidos diferentes não agrupam, mesmo com o original em português igual. */
    private function translatedValuesDoNotGroupWithOriginal(): void
    {
        $profile = $this->hours(array_fill_keys(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], '9h–18h'));
        [$repo, $translator] = $this->setUp($profile, ['en' => ['hours.monday' => '9am–6pm', 'hours.tuesday' => '9am–5pm', 'hours.wednesday' => '9am–6pm', 'hours.thursday' => '9am–6pm', 'hours.friday' => '9am–6pm']]);
        $this->assertSame('Seg a Sex: 9h–18h', $this->tag($repo, $translator, Language::PT_PT));
        $this->assertSame('Mon: 9am–6pm · Tue: 9am–5pm · Wed to Fri: 9am–6pm', $this->tag($repo, $translator, Language::EN));
    }

    /** 5. Dia sem tradução usa o valor principal e agrupa pelo valor resultante. */
    private function fallbackPerDay(): void
    {
        $profile = $this->hours(array_fill_keys(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], '9h–18h'));
        [$repo, $translator] = $this->setUp($profile, ['en' => ['hours.monday' => '9am–6pm', 'hours.tuesday' => '9am–6pm', 'hours.friday' => '9am–6pm']]);
        $this->assertSame('Mon and Tue: 9am–6pm · Wed and Thu: 9h–18h · Fri: 9am–6pm', $this->tag($repo, $translator, Language::EN));
        // Francês sem nenhuma tradução: tudo cai no principal e agrupa como em português.
        $this->assertSame('Lun à Ven: 9h–18h', $this->tag($repo, $translator, Language::FR));
    }

    /** 6. Observação traduzida e fallback da observação. */
    private function notes(): void
    {
        $profile = $this->hours(['monday' => '9h–18h'], 'Fechado em feriados');
        [$repo, $translator, $module] = $this->setUp($profile, ['en' => ['hours.notes' => 'Closed on holidays']]);
        $this->assertSame('Mon: 9h–18h · Closed on holidays', $this->tag($repo, $translator, Language::EN));
        $this->assertSame('Lun: 9h–18h · Fechado em feriados', $this->tag($repo, $translator, Language::FR), 'Sem tradução: observação do idioma principal.');
        $this->assertSame('Seg: 9h–18h · Fechado em feriados', $this->tag($repo, $translator, Language::PT_PT));
        $this->context->now = Language::EN;
        $this->assertSame("Mon: 9h–18h<br />\nClosed on holidays", $this->shortcode($module));
        $this->context->now = Language::PT_PT;

        // Só observação.
        [$repo, $translator] = $this->setUp($this->hours([], 'Atendimento por marcação'), ['en' => ['hours.notes' => 'By appointment']]);
        $this->assertSame('By appointment', $this->tag($repo, $translator, Language::EN));
        $this->assertSame('Atendimento por marcação', $this->tag($repo, $translator, Language::ES));
    }

    /** 7. O shortcode continua um dia por linha, agora com os dias e os valores do idioma. */
    private function shortcodeStaysDaily(): void
    {
        $profile = $this->hours(['monday' => '9h–18h', 'tuesday' => '9h–18h', 'wednesday' => '9h–18h', 'saturday' => '10h–13h', 'sunday' => 'Fechado']);
        [, , $module] = $this->setUp($profile, ['en' => ['hours.monday' => '9am–6pm', 'hours.tuesday' => '9am–6pm', 'hours.sunday' => 'Closed']]);
        $this->context->now = Language::EN;
        $this->assertSame("Mon: 9am–6pm<br />\nTue: 9am–6pm<br />\nWed: 9h–18h<br />\nSat: 10h–13h<br />\nSun: Closed", $this->shortcode($module));
        $this->context->now = Language::FR;
        $this->assertSame("Lun: 9h–18h<br />\nMar: 9h–18h<br />\nMer: 9h–18h<br />\nSam: 10h–13h<br />\nDim: Fechado", $this->shortcode($module));
        $this->context->now = Language::ES;
        $this->assertSame("Lun: 9h–18h<br />\nMar: 9h–18h<br />\nMié: 9h–18h<br />\nSáb: 10h–13h<br />\nDom: Fechado", $this->shortcode($module));
        $this->context->now = Language::PT_PT;
        $this->assertSame("Seg: 9h–18h<br />\nTer: 9h–18h<br />\nQua: 9h–18h<br />\nSáb: 10h–13h<br />\nDom: Fechado", $this->shortcode($module));
    }

    /** 9. A API pública não infere idioma: opening_hours continua em português, mesmo com a página em inglês. */
    private function publicApiStaysPortuguese(): void
    {
        $profile = $this->hours(['monday' => '9h–18h', 'tuesday' => '9h–18h', 'saturday' => '10h–13h'], 'Fechado em feriados');
        [$repo] = $this->setUp($profile, ['en' => ['hours.monday' => '9am–6pm', 'hours.notes' => 'Closed on holidays']]);
        $this->context->now = Language::EN;
        $this->assertSame("Seg e Ter: 9h–18h\nSáb: 10h–13h\nFechado em feriados", $repo->getProfile()->toLegacyArray()['opening_hours']);
        $this->assertSame("Seg e Ter: 9h–18h\nSáb: 10h–13h\nFechado em feriados", $repo->getProfile()->getOpeningHours());
        $this->context->now = Language::PT_PT;
    }
}
