--TEST--
MessageTranslationReader filters all owner domains, active locales and translation states without changing data
--FILE--
<?php

use App\Modules\Translation\Api\MessageTranslationReader;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslationMessageStatus;
use App\Shared\Domain\Locale\Locale;
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
/** @var MessageTranslationReader $reader */
$reader = $integration->container->getByType(MessageTranslationReader::class);

Assert::type(MessageTranslationReader::class, $reader);

$token = bin2hex(random_bytes(8));
$coreKey = 'f4_17.core.' . $token;
$customKey = 'f4_17.custom.' . $token;
$outdatedKey = 'f4_17.outdated.' . $token;
$adminKey = 'f4_17.admin.' . $token;

$pdo->beginTransaction();

try {
    $pdo->prepare(
        "INSERT INTO catalog_message_translation (message_key, locale, message_text, source) VALUES (?, 'cs', 'Systémový text', 'core')",
    )->execute([$coreKey]);
    $pdo->prepare(
        "INSERT INTO catalog_message_translation (message_key, locale, message_text, source, is_customized) VALUES (?, 'cs', 'Vlastní text', 'custom', 1)",
    )->execute([$customKey]);
    $pdo->prepare(
        "INSERT INTO catalog_message_translation (message_key, locale, message_text, source, is_outdated) VALUES (?, 'cs', 'Zastaralý text', 'core', 1)",
    )->execute([$outdatedKey]);
    $pdo->prepare(
        "INSERT INTO admin_message_translation (message_key, locale, message_text) VALUES (?, 'cs', 'Admin text')",
    )->execute([$adminKey]);

    $all = $reader->search(locale: Locale::fromString('cs'));
    Assert::true(count(array_filter($all, static fn($row): bool => $row->messageKey === $coreKey)) === 1);
    Assert::true(count(array_filter($all, static fn($row): bool => $row->messageKey === $adminKey)) === 1);

    $core = $reader->search(TranslationDomain::Catalog, Locale::fromString('cs'), TranslationMessageStatus::Core);
    Assert::true(count(array_filter($core, static fn($row): bool => $row->messageKey === $coreKey)) === 1);
    Assert::same([], array_values(array_filter($core, static fn($row): bool => $row->messageKey === $customKey)));

    $custom = $reader->search(TranslationDomain::Catalog, Locale::fromString('cs'), TranslationMessageStatus::Custom);
    Assert::true(count(array_filter($custom, static fn($row): bool => $row->messageKey === $customKey)) === 1);

    $customized = $reader->search(TranslationDomain::Catalog, Locale::fromString('cs'), TranslationMessageStatus::Customized);
    Assert::true(count(array_filter($customized, static fn($row): bool => $row->messageKey === $customKey)) === 1);

    $outdated = $reader->search(TranslationDomain::Catalog, Locale::fromString('cs'), TranslationMessageStatus::Outdated);
    Assert::true(count(array_filter($outdated, static fn($row): bool => $row->messageKey === $outdatedKey)) === 1);

    Assert::same(1, (int) $pdo->query(
        'SELECT COUNT(*) FROM catalog_message_translation WHERE message_key = ' . $pdo->quote($coreKey),
    )->fetchColumn());
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "OK\n";
?>
--EXPECT--
OK
