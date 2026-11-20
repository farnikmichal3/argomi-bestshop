--TEST--
Debug mode is enabled only for the explicit development environment
--FILE--
<?php

use App\Core\Environment\DebugModePolicy;
use Tester\Assert;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$policy = new DebugModePolicy();

Assert::true($policy->isEnabled('development'));
Assert::false($policy->isEnabled(null));
Assert::false($policy->isEnabled('production'));
Assert::false($policy->isEnabled('testing'));
Assert::false($policy->isEnabled('Development'));
Assert::false($policy->isEnabled(''));
Assert::false($policy->isEnabled(true));
Assert::false($policy->isEnabled(1));

echo "OK\n";
?>
--EXPECT--
OK
