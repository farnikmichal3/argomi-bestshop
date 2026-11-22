<?php

declare(strict_types=1);

use App\Modules\Identity\Application\AdminAuthenticator;
use App\Modules\Identity\Application\AdminAuthenticationRepository;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
$database = $integration->database;
$repository = new AdminAuthenticationRepository($database);
$authenticator = new AdminAuthenticator($repository);

$email = 'identity-test-' . bin2hex(random_bytes(6)) . '@example.test';
$password = 'Correct-Horse-Battery-42!';
$hash = password_hash($password, PASSWORD_DEFAULT);
Assert::type('string', $hash);

$user = $database->table('admin_user')->insert([
    'email' => $email,
    'password_hash' => $hash,
    'status' => 'active',
]);
$userId = (int) $user->id;

try {
    $roleIds = $database->table('admin_role')->fetchPairs('code', 'id');
    Assert::true(isset($roleIds['catalog_manager'], $roleIds['translation_manager']));

    $database->table('admin_user_role')->insert([
        ['admin_user_id' => $userId, 'admin_role_id' => $roleIds['catalog_manager']],
        ['admin_user_id' => $userId, 'admin_role_id' => $roleIds['translation_manager']],
    ]);

    $identity = $authenticator->authenticate(strtoupper($email), $password);
    Assert::same($userId, $identity->getId());
    Assert::same(['catalog_manager', 'translation_manager'], $identity->getRoles());
    Assert::same(['email' => $email], $identity->getData());

    $updatedAccount = $database->table('admin_user')->get($userId);
    Assert::notSame(null, $updatedAccount->last_login_at);

    Assert::exception(
        static fn () => $authenticator->authenticate($email, 'wrong-password'),
        AuthenticationException::class,
    );

    $database->table('admin_user')->where('id', $userId)->update(['status' => 'disabled']);
    Assert::exception(
        static fn () => $authenticator->authenticate($email, $password),
        AuthenticationException::class,
    );
} finally {
    $database->table('admin_user')->where('id', $userId)->delete();
}

echo "OK\n";
?>
--EXPECT--
OK
