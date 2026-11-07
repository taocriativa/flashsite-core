<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Modules\Blocks\OpeningHours;

/** Leitura do horário em texto para o bloco "Aberto agora": na dúvida, não mostra. */
final class OpeningHoursTest extends TestCase
{
    public function run(): void
    {
        $this->assertSame([], OpeningHours::parseDay('Encerrado'));
        $this->assertSame([[750, 900], [1170, 1380]], OpeningHours::parseDay('12h30–15h e 19h30–23h'));
        $this->assertSame([[540, 1080]], OpeningHours::parseDay('9:00 - 18:00'));
        $this->assertSame([[1200, 1560]], OpeningHours::parseDay('20h–2h')); // passa da meia-noite
        $this->assertSame(null, OpeningHours::parseDay('Só com marcação'));
        $this->assertSame(null, OpeningHours::parseDay('12h30–15h, jantar a partir das 19h'));

        $week = OpeningHours::parseWeek(['monday' => 'Encerrado', 'tuesday' => '12h–15h', 'wednesday' => '12h–15h', 'thursday' => '12h–15h', 'friday' => '12h–15h', 'saturday' => '12h–15h', 'sunday' => '12h–16h']);
        $this->assertSame([[720, 900]], $week['tuesday']);
        // Um dia ilegível invalida a semana inteira.
        $this->assertSame(null, OpeningHours::parseWeek(['monday' => 'Depende', 'tuesday' => '12h–15h']));
        // Tudo fechado/vazio: nada a mostrar.
        $this->assertSame(null, OpeningHours::parseWeek([]));
    }
}
