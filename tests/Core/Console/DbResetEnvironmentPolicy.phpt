--TEST--
DbResetEnvironmentPolicy allows only the explicitly configured development environment
--FILE--
<?php

use App\Shared\Infrastructure\Console\DbResetEnvironmentPolicy;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

Assert::true((new DbResetEnvironmentPolicy('development'))->isAllowed());

foreach ([
    null,
    '',
    'unknown',
    'testing',
    'staging',
    'production',
    'Development',
    ' development ',
] as $environment) {
    Assert::false(
        (new DbResetEnvironmentPolicy($environment))->isAllowed(),
        sprintf('Environment %s must not be allowed.', var_export($environment, true)),
    );
}

echo "OK\n";
?>
--EXPECT--
OK
