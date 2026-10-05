<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\Collections\ItemSanitizer;
use FlashSite\Core\Domain\Collections\ItemValidator;

final class CollectionItemSanitizeValidateTest extends TestCase
{
    public function run(): void
    {
        $preset = CollectionRegistry::fromDirectory(dirname(__DIR__) . '/fixtures/collections')->get('teste');
        $s = new ItemSanitizer();
        $v = new ItemValidator();

        // Dinheiro em formato português (00,00) e variantes.
        $this->assertSame(285000.0, ItemSanitizer::parseNumber('285.000,00'));
        $this->assertSame(285000.0, ItemSanitizer::parseNumber('285 000,00 €'));
        $this->assertSame(285000.0, ItemSanitizer::parseNumber('285.000'));
        $this->assertSame(1250.5, ItemSanitizer::parseNumber('1250,5'));
        $this->assertSame(1250.5, ItemSanitizer::parseNumber('1250.50'));
        $this->assertSame(1234567.89, ItemSanitizer::parseNumber('1.234.567,89'));
        $this->assertSame(null, ItemSanitizer::parseNumber('sob consulta'));
        $this->assertSame(null, ItemSanitizer::parseNumber(''));
        $this->assertSame(12.35, $s->sanitize($preset->field('preco'), '12,345'));

        // Bool, select, multiselect.
        $this->assertSame(true, $s->sanitize($preset->field('preco_sob_consulta'), 'sim'));
        $this->assertSame(false, $s->sanitize($preset->field('preco_sob_consulta'), '0'));
        $this->assertSame('a+', $s->sanitize($preset->field('classe'), 'A+'));
        $this->assertSame('', $s->sanitize($preset->field('classe'), 'Z'));
        $this->assertSame(['gluten'], $s->sanitize($preset->field('alergenios'), ['Gluten', 'gluten', 'inventado']));

        // Galeria mantém ordem e remove duplicados/zeros.
        $this->assertSame([12, 7, 3], $s->sanitize($preset->field('galeria'), ['12', 7, '0', 12, 3]));
        $this->assertSame([5, 9], $s->sanitize($preset->field('galeria'), '5,9'));

        // Coordenadas, datas, horas, listas.
        $this->assertSame(['lat' => 38.7223, 'lng' => -9.1393], $s->sanitize($preset->field('coordenadas'), '38.7223, -9.1393'));
        $this->assertSame([], $s->sanitize($preset->field('coordenadas'), ['lat' => 120, 'lng' => 0]));
        $this->assertSame('2026-10-18', $s->sanitize($preset->field('data'), '18/10/2026'));
        $this->assertSame('', $s->sanitize($preset->field('data'), '31/02/2026'));
        $this->assertSame('23:00', $s->sanitize($preset->field('hora_inicio'), '23h'));
        $this->assertSame(['Avaliação inicial', 'Plano mensal'], $s->sanitize($preset->field('itens'), "Avaliação inicial\n\nPlano mensal"));
        $this->assertSame('ABCDEFGHIJKLMNOPQRST', $s->sanitize($preset->field('referencia'), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'));

        // Checkbox ausente no POST conta como false.
        $all = $s->sanitizeAll($preset, ['referencia' => 'IMO-1', 'preco' => '100,00']);
        $this->assertSame(false, $all['preco_sob_consulta']);
        $this->assertFalse(array_key_exists('quartos', $all), 'Campos não enviados não são tocados.');

        // Validação: obrigatório, mínimo/máximo, regras transversais.
        $errors = $v->validate($preset, $s->sanitizeAll($preset, ['preco' => '', 'quartos' => '99']));
        $this->assertArrayHasKey('referencia', $errors);
        $this->assertArrayHasKey('preco', $errors);
        $this->assertSame('Indique "Preço" ou marque "Preço sob consulta".', $errors['preco']);
        $this->assertSame('O campo "Quartos" não pode ser superior a 50.', $errors['quartos']);

        $ok = $v->validate($preset, $s->sanitizeAll($preset, ['referencia' => 'IMO-1', 'preco_sob_consulta' => '1']));
        $this->assertSame([], $ok, 'Preço sob consulta dispensa o preço.');

        $horas = $v->validate($preset, $s->sanitizeAll($preset, ['referencia' => 'R', 'preco' => '10', 'hora_inicio' => '23:00', 'hora_fim' => '22:00']));
        $this->assertArrayHasKey('hora_fim', $horas);

        $negativo = $v->validate($preset, $s->sanitizeAll($preset, ['referencia' => 'R', 'preco' => '-5']));
        $this->assertSame('O campo "Preço" não pode ser inferior a 0.', $negativo['preco']);
    }
}
