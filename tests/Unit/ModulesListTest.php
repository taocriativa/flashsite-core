<?php
declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

use FlashSite\Core\Core\Contracts\ModuleInterface;

/**
 * Todos os módulos de config/modules.php carregam e cumprem o contrato (@since 3.2.0).
 * Um módulo que não implementa o contrato derruba o site inteiro ao ativar o plugin.
 */
final class ModulesListTest extends TestCase
{
    public function run(): void
    {
        $modules = require dirname(__DIR__, 2) . '/config/modules.php';
        $this->assertTrue(is_array($modules) && $modules !== [], 'Lista de módulos vazia.');
        foreach ($modules as $class) {
            $this->assertTrue(class_exists($class), "Classe inexistente: $class");
            $reflection = new ReflectionClass($class);
            $this->assertTrue($reflection->implementsInterface(ModuleInterface::class), "$class não implementa ModuleInterface.");
            $this->assertFalse($reflection->isAbstract(), "$class está incompleta (métodos do contrato em falta).");
        }
    }
}
