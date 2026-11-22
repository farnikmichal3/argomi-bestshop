--TEST--
First administrator provisioning creates an active account with the base admin role
--FILE--
<?php

use App\Modules\Identity\Application\AdminAuthenticationRepository;
use App\Modules\Identity\Application\AdminAuthenticator;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
$repository = new AdminAuthenticationRepository($integration->database);

$email = 'identity-provision-' . bin2hex(random_bytes(6)) . '@example.test';
$password = 'Correct-Horse-Battery-42!';
$hash = password_hash($password, PASSWORD_DEFAULT);
Assert::type('string', $hash);

$adminId = $repository->createAdministrator($email, $hash);

try {
    $identity = (new AdminAuthenticator($repository))->authenticate($email, $password);

    Assert::same($adminId, $identity->getId());
    Assert::same(['admin'], $identity->getRoles());
    Assert::same(['email' => $email], $identity->getData());
} finally {
    $integration->database->table('admin_user')->where('id', $adminId)->delete();
}

echo "OK\n";
?>
--EXPECT--
OK
