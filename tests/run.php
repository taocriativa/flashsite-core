<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$files = [
    __DIR__ . '/Unit/BusinessProfileTest.php',
    __DIR__ . '/Unit/BusinessValidatorTest.php',
    __DIR__ . '/Unit/PublicSerializerTest.php',
    __DIR__ . '/Unit/OutputResolverTest.php',
    __DIR__ . '/Unit/ContainerAndModuleManagerTest.php',
    __DIR__ . '/Unit/ElementorTagManifestTest.php',
    __DIR__ . '/Integration/BusinessRepositoryTest.php',
    __DIR__ . '/Integration/ManagedHeroRepositoryTest.php',
    __DIR__ . '/Integration/ManagedHeroRenderModuleTest.php',
    __DIR__ . '/Integration/OnboardingAndRoleManagerTest.php',
    __DIR__ . '/Integration/ApiAccessModuleTest.php',
    __DIR__ . '/Integration/ApiConsolidationTest.php',
    __DIR__ . '/Integration/ApiWriteModuleTest.php',
    __DIR__ . '/Integration/OutputPrivacyTest.php',
    __DIR__ . '/Integration/DataSafetyTest.php',
    __DIR__ . '/Integration/OutputFoundationShortcodeTest.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

$tests = [
    new BusinessProfileTest(),
    new BusinessValidatorTest(),
    new PublicSerializerTest(),
    new OutputResolverTest(),
    new ContainerAndModuleManagerTest(),
    new ElementorTagManifestTest(),
    new BusinessRepositoryTest(),
    new OnboardingAndRoleManagerTest(),
    new ApiAccessModuleTest(),
    new ApiConsolidationTest(),
    new ApiWriteModuleTest(),
    new OutputPrivacyTest(),
    new DataSafetyTest(),
    new OutputFoundationShortcodeTest(),
];

$failures = [];
foreach ($tests as $test) {
    $name = $test::class;
    try {
        $test->run();
        echo "[PASS] {$name}\n";
    } catch (Throwable $e) {
        $failures[] = $name . ': ' . $e->getMessage();
        echo "[FAIL] {$name} — {$e->getMessage()}\n";
    }
}

echo "\nExecuted " . count($tests) . " test groups.\n";
if ($failures !== []) {
    echo "Failures: " . count($failures) . "\n";
    exit(1);
}

echo "All tests passed.\n";
