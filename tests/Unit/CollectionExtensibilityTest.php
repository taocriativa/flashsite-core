<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Domain\Collections\CollectionRegistry;
use FlashSite\Core\Domain\I18n\Language;
use FlashSite\Core\Domain\I18n\Strings;

/**
 * 3.4.0-beta.1: porta de extensão (pastas externas, prefixo x_, colisões, pacotes de tradução)
 * e garantia de que imovel, plano e prato continuam idênticos ao Core 3.3.0.
 */
final class CollectionExtensibilityTest extends TestCase
{
    public function run(): void
    {
        $this->externalDirectories();
        $this->registryOrigins();
        $this->legacyPresetsUnchanged();
        $this->i18nPacks();
    }

    private function externalDirectories(): void
    {
        flashsite_reset_test_state();
        $core = dirname(__DIR__) . '/fixtures/collections';
        $external = dirname(__DIR__) . '/fixtures/external';

        $this->assertSame([], CollectionRegistry::externalDirectories($core), 'Sem filtro não há pastas externas.');

        add_filter(CollectionRegistry::FILTER_DIRS, static fn (array $dirs): array => array_merge($dirs, [$external, $external, '/nao/existe', 42, $core]));
        $dirs = CollectionRegistry::externalDirectories($core);
        $this->assertCount(1, $dirs, 'Duplicadas, inexistentes, não-texto e a pasta do Core são descartadas.');
        $this->assertSame(realpath($external), $dirs[0]);
        flashsite_reset_test_state();
    }

    private function registryOrigins(): void
    {
        $core = dirname(__DIR__) . '/fixtures/collections';
        $external = dirname(__DIR__) . '/fixtures/external';
        $registry = CollectionRegistry::fromDirectories($core, [$external]);

        $this->assertTrue($registry->has('teste'));
        $this->assertSame(CollectionRegistry::ORIGIN_CORE, $registry->origin('teste'));
        $this->assertFalse($registry->isExternal('teste'));

        $this->assertTrue($registry->has('x_agenda'), 'Preset externo válido carrega.');
        $this->assertTrue($registry->isExternal('x_agenda'));
        $this->assertSame('fs_x_agenda', $registry->get('x_agenda')->postType());

        $errors = $registry->errors();
        $this->assertFalse($registry->has('agenda'), 'Externo sem prefixo x_ é recusado.');
        $this->assertArrayHasKey('externo/sem_prefixo.php', $errors);
        $this->assertFalse($registry->has('x_colide'), 'Colisão de slug com um preset do Core recusa o externo.');
        $this->assertArrayHasKey('externo/x_colide_slug.php', $errors);
        $this->assertArrayHasKey('externo/x_tenta_oficial.php', $errors, 'Externo nunca ocupa a chave de um oficial.');
        $this->assertSame(CollectionRegistry::ORIGIN_CORE, $registry->origin('teste'), 'O oficial continua intacto.');
        $this->assertArrayHasKey('invalido.php', $errors, 'Os erros do Core continuam a ser reportados com o nome do ficheiro.');

        // fromDirectory() (assinatura antiga) continua a funcionar e só tem presets do Core.
        $legacy = CollectionRegistry::fromDirectory($core);
        $this->assertTrue($legacy->has('teste'));
        $this->assertFalse($legacy->has('x_agenda'));
    }

    private function legacyPresetsUnchanged(): void
    {
        $golden = json_decode((string) file_get_contents(dirname(__DIR__) . '/fixtures/golden/presets-3.3.0.json'), true);
        $this->assertCount(6, $golden);
        $dir = dirname(__DIR__, 2) . '/config/collections';
        foreach (['PT', 'BR'] as $market) {
            $registry = CollectionRegistry::fromDirectories($dir, [], $market);
            $this->assertSame([], $registry->errors(), 'Os presets oficiais carregam sem erro.');
            foreach (['imovel', 'plano', 'prato'] as $key) {
                $preset = $registry->get($key);
                $this->assertTrue($preset !== null, "Preset oficial $key presente.");
                $this->assertSame(
                    $golden["$key:$market"],
                    sha1((string) json_encode($preset->toArray(), JSON_UNESCAPED_UNICODE)),
                    "$key ($market) tem de ser idêntico ao Core 3.3.0."
                );
                $this->assertSame(CollectionRegistry::ORIGIN_CORE, $registry->origin($key));
                $this->assertFalse(str_starts_with($key, CollectionRegistry::EXTERNAL_PREFIX));
            }
        }
    }

    private function i18nPacks(): void
    {
        flashsite_reset_test_state();
        $core = dirname(__DIR__, 2) . '/config/i18n';
        $extra = dirname(__DIR__) . '/fixtures/external_i18n';

        Strings::useDirectory($core);
        $this->assertSame('See all', Strings::t('Ver todos', Language::EN), 'Pacote do Core inalterado sem filtro.');
        $this->assertSame('Sessões do kit', Strings::t('Sessões do kit', Language::EN), 'Sem filtro, texto externo fica em português.');

        Strings::useDirectory($core);
        add_filter('flashsite/i18n/pack_dirs', static fn (array $dirs): array => array_merge($dirs, [$extra, '/nao/existe']));
        $this->assertSame('Kit sessions', Strings::t('Sessões do kit', Language::EN), 'Pacote externo acrescenta frases.');
        $this->assertSame('See all', Strings::t('Ver todos', Language::EN), 'Em conflito o pacote do Core vence.');
        $this->assertSame('Kit sessions', Strings::t('Sessões do kit', Language::ES), 'Idioma sem pacote próprio recua para o pacote en, como no Core.');

        Strings::useDirectory(null);
        flashsite_reset_test_state();
    }
}
