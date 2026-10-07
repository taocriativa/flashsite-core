<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Modules\Markets\MarketsModule;

final class MarketsModuleTest extends TestCase
{
    public function run(): void
    {
        $pairs = [['PT' => 10, 'BR' => 20], ['PT' => 11, 'BR' => 21]];
        $this->assertSame(['market' => 'PT', 'pages' => ['PT' => 10, 'BR' => 20]], MarketsModule::pairFor($pairs, 10));
        $this->assertSame(['market' => 'BR', 'pages' => ['PT' => 11, 'BR' => 21]], MarketsModule::pairFor($pairs, 21));
        $this->assertSame(null, MarketsModule::pairFor($pairs, 99), 'Página sem par não tem versões.');
        $this->assertSame(null, MarketsModule::pairFor($pairs, 0));

        $links = MarketsModule::hreflangLinks(['PT' => 'https://x.pt/', 'BR' => 'https://x.pt/br/'], 'PT');
        $this->assertSame([
            ['hreflang' => 'pt-PT', 'href' => 'https://x.pt/'],
            ['hreflang' => 'pt-BR', 'href' => 'https://x.pt/br/'],
            ['hreflang' => 'x-default', 'href' => 'https://x.pt/'],
        ], $links);
        $this->assertSame('https://x.pt/br/', MarketsModule::hreflangLinks(['PT' => 'https://x.pt/', 'BR' => 'https://x.pt/br/'], 'BR')[2]['href'], 'x-default segue a versão principal.');
        $this->assertSame([], MarketsModule::hreflangLinks(['PT' => 'https://x.pt/', 'BR' => ''], 'PT'), 'Sem as duas versões, não há hreflang.');
    }
}
