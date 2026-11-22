--TEST--
Admin Identity foundation creates account, role, and assignment tables with expected constraints and seed roles
--FILE--
<?php

use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var Explorer $database */
$database = $integration->database;
$pdo = $database->getConnection()->getPdo();

$expectedTables = ['admin_user', 'admin_role', 'admin_user_role'];
$actualTables = $pdo->query(
    "SELECT TABLE_NAME
     FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_TYPE = 'BASE TABLE'
       AND TABLE_NAME IN ('admin_user', 'admin_role', 'admin_user_role')",
)->fetchAll(PDO::FETCH_COLUMN);

sort($expectedTables);
sort($actualTables);
Assert::same($expectedTables, $actualTables);

$adminUserColumns = $pdo->query(
    "SELECT COLUMN_NAME
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'admin_user'",
)->fetchAll(PDO::FETCH_COLUMN);

Assert::true(in_array('email', $adminUserColumns, true));
Assert::true(in_array('password_hash', $adminUserColumns, true));
Assert::true(in_array('status', $adminUserColumns, true));
Assert::true(in_array('last_login_at', $adminUserColumns, true));

$roleCodes = $database->table('admin_role')->fetchPairs('code', 'id');
Assert::true(isset($roleCodes['admin']));
Assert::true(isset($roleCodes['catalog_viewer']));
Assert::true(isset($roleCodes['catalog_manager']));
Assert::true(isset($roleCodes['translation_manager']));

$assignmentUnique = $pdo->query(
    "SELECT COUNT(DISTINCT INDEX_NAME)
     FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'admin_user_role'
       AND INDEX_NAME = 'uq_admin_user_role'
       AND NON_UNIQUE = 0",
)->fetchColumn();
Assert::same(1, (int) $assignmentUnique);

echo "OK\n";
?>
--EXPECT--
OK
