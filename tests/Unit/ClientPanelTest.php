<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Modules\ClientPanel\ClientPanelModule;
use FlashSite\Core\Modules\ClientPanel\PtBrAdmin;

/** Painel do cliente (@since 3.1.0): PT-BR nas telas e quem é "Cliente". */
final class ClientPanelTest extends TestCase
{
    public function run(): void
    {
        $this->assertSame('Salvar alterações', PtBrAdmin::convertText('Guardar alterações'));
        $this->assertSame('O arquivo foi salvo. Endereço e CEP.', PtBrAdmin::convertText('O ficheiro foi guardado. Morada e código postal.'));
        $this->assertSame('Fotos e arquivos · Sua conta · senha', PtBrAdmin::convertText('Fotos e ficheiros · A sua conta · palavra-passe'));
        // Palavras inteiras apenas; identificadores ficam.
        $this->assertSame('guardarias fs_guardar guardar-x', PtBrAdmin::convertText('guardarias fs_guardar guardar-x'));

        $html = '<form><input name="guardar" value="Guardar"><a href="/contacto/">Contacto</a><script>var guardar = 1;</script><textarea>Morada do cliente</textarea></form>';
        $this->assertSame('<form><input name="guardar" value="Salvar"><a href="/contacto/">Contato</a><script>var guardar = 1;</script><textarea>Morada do cliente</textarea></form>', PtBrAdmin::convert($html));

        $client = (object) ['roles' => ['flashsite_site_manager']];
        $admin = (object) ['roles' => ['administrator', 'flashsite_site_manager']];
        $this->assertTrue(ClientPanelModule::isClient($client));
        $this->assertSame(false, ClientPanelModule::isClient($admin));
        $this->assertSame(false, ClientPanelModule::isClient((object) ['roles' => ['editor']]));
    }
}
