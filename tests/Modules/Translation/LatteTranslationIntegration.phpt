--TEST--
Latte templates translate database keys at runtime with explicit locale and domain
--FILE--
<?php

use App\Modules\Translation\Domain\MessageDomain;
use App\Modules\Translation\Infrastructure\Persistence\DatabaseMessageStore;
use App\Modules\Translation\Infrastructure\Translation\DatabaseTranslatorFactory;
use App\Shared\Domain\Locale\Locale;
use App\Shared\Infrastructure\Persistence\Locale\DatabaseLocaleProvider;
use Nette\Application\UI\TemplateFactory;
use Nette\Bridges\ApplicationLatte\Template as LatteTemplate;
use RuntimeException;
use Tests\Support\IntegrationTestBootstrap;
use Tester\Assert;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
$pdo = $integration->database->getConnection()->getPdo();
$templateFactory = $integration->container->getByType(TemplateFactory::class);

// Přímé úložiště brání závislosti testu na sdílené persistentní cache.
$factory = new DatabaseTranslatorFactory(
    new DatabaseMessageStore($integration->database),
    new DatabaseLocaleProvider($integration->database),
);

$token = bin2hex(random_bytes(8));
$titleKey = 'f4_08.title.' . $token;
$greetingKey = 'f4_08.greeting.' . $token;
$fallbackKey = 'f4_08.fallback.' . $token;
$unsafeKey = 'f4_08.unsafe.' . $token;

$pdo->beginTransaction();

try {
    $rows = [
        ['en', $titleKey, 'Catalog in English'],
        ['cs', $titleKey, 'Katalog česky'],
        ['en', $greetingKey, 'Hello, {name}!'],
        ['cs', $fallbackKey, 'Výchozí překlad z databáze'],
        ['en', $unsafeKey, '<script>alert(1)</script>'],
    ];

    $statement = $pdo->prepare(
        'INSERT INTO catalog_message_translation (message_key, locale, message_text) VALUES (?, ?, ?)',
    );

    foreach ($rows as [$locale, $key, $message]) {
        $statement->execute([$key, $locale, $message]);
    }

    $englishTranslator = $factory->create(MessageDomain::Catalog, Locale::fromString('en'));
    $czechTranslator = $factory->create(MessageDomain::Catalog, Locale::fromString('cs'));

    $englishTemplate = $templateFactory->createTemplate();

    if (!$englishTemplate instanceof LatteTemplate) {
        throw new RuntimeException('Nette did not create the expected Latte template.');
    }

    $englishTemplate->setTranslator($englishTranslator);
    $englishHtml = $englishTemplate->renderToString(
        __DIR__ . '/fixtures/latte-translation.latte',
        [
            'name' => 'Alice',
            'titleKey' => $titleKey,
            'greetingKey' => $greetingKey,
            'dynamicKey' => $titleKey,
            'fallbackKey' => $fallbackKey,
            'unsafeKey' => $unsafeKey,
        ],
    );

    Assert::contains('<h1>Catalog in English</h1>', $englishHtml);
    Assert::contains('<p>Hello, Alice!</p>', $englishHtml);
    Assert::contains('<p>Výchozí překlad z databáze</p>', $englishHtml);
    Assert::contains('&lt;script&gt;alert(1)&lt;/script&gt;', $englishHtml);
    Assert::notContains('<script>alert(1)</script>', $englishHtml);
    Assert::contains('<span>Catalog in English</span>', $englishHtml);

    // Druhá šablona používá jiný překladač; locale se nesmí přenést z předchozího renderu.
    $czechTemplate = $templateFactory->createTemplate();

    if (!$czechTemplate instanceof LatteTemplate) {
        throw new RuntimeException('Nette did not create the expected Latte template.');
    }

    $czechTemplate->setTranslator($czechTranslator);
    $czechHtml = $czechTemplate->renderToString(
        __DIR__ . '/fixtures/latte-translation.latte',
        [
            'name' => 'Alice',
            'titleKey' => $titleKey,
            'greetingKey' => $greetingKey,
            'dynamicKey' => $titleKey,
            'fallbackKey' => $fallbackKey,
            'unsafeKey' => $unsafeKey,
        ],
    );

    Assert::contains('<h1>Katalog česky</h1>', $czechHtml);
    Assert::contains('<span>Katalog česky</span>', $czechHtml);
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "OK\n";
?>
--EXPECT--
OK
