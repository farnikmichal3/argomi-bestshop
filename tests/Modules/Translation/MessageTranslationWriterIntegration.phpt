--TEST--
MessageTranslationWriter creates, updates and deletes custom translations, invalidates cache and never changes core sources
--FILE--
<?php

use App\Modules\Translation\Api\CustomMessageNotFoundException;
use App\Modules\Translation\Api\MessageTranslationWriter;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslatorFactory;
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
/** @var MessageTranslationWriter $writer */
$writer = $integration->container->getByType(MessageTranslationWriter::class);
/** @var TranslatorFactory $translatorFactory */
$translatorFactory = $integration->container->getByType(TranslatorFactory::class);

$locale = Locale::fromString('cs');
$token = bin2hex(random_bytes(8));
$customKey = 'f4_18.custom.' . $token;
$coreKey = 'button.add_to_cart';

$pdo->beginTransaction();

try {
    $writer->createCustom(TranslationDomain::Catalog, $customKey, $locale, 'Původní vlastní text');

    $before = $translatorFactory->createTranslator(TranslationDomain::Catalog, $locale);
    Assert::same('Původní vlastní text', $before->translate($customKey));

    $writer->updateCustom(TranslationDomain::Catalog, $customKey, $locale, 'Aktualizovaný vlastní text');

    $after = $translatorFactory->createTranslator(TranslationDomain::Catalog, $locale);
    Assert::same('Aktualizovaný vlastní text', $after->translate($customKey));

    Assert::exception(
        static fn() => $writer->updateCustom(TranslationDomain::Catalog, $coreKey, $locale, 'Nesmí přepsat systémový text'),
        CustomMessageNotFoundException::class,
    );

    Assert::exception(
        static fn() => $writer->deleteCustom(TranslationDomain::Catalog, $coreKey, $locale),
        CustomMessageNotFoundException::class,
    );

    $writer->deleteCustom(TranslationDomain::Catalog, $customKey, $locale);
    $row = $pdo->prepare(
        'SELECT COUNT(*) FROM catalog_message_translation WHERE message_key = ? AND locale = ?',
    );
    $row->execute([$customKey, $locale->getCode()]);
    Assert::same(0, (int) $row->fetchColumn());

    Assert::exception(
        static fn() => $writer->createCustom(TranslationDomain::Catalog, 'invalid key', $locale, 'Text'),
        InvalidArgumentException::class,
    );

    Assert::exception(
        static fn() => $writer->createCustom(TranslationDomain::Catalog, 'f4_18.html.' . $token, $locale, '<b>HTML</b>'),
        InvalidArgumentException::class,
    );
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "OK\n";
?>
--EXPECT--
OK
