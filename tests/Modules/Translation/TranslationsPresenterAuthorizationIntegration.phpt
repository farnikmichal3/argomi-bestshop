<?php

declare(strict_types=1);

use App\Modules\Translation\Api\LocaleManager;
use App\Modules\Translation\Api\MessageTranslationReader;
use App\Modules\Translation\Api\MessageTranslationWriter;
use App\Modules\Translation\Api\TranslationDomain;
use App\Modules\Translation\Api\TranslationPermission;
use App\Modules\Translation\Api\TranslatorFactory;
use App\Shared\Domain\Locale\LocaleProvider;
use App\UI\Admin\Translations\TranslationsPresenter;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

final class TestableTranslationsPresenter extends TranslationsPresenter
{
    public function manageForm(): Form
    {
        return $this->createComponentManageForm();
    }
}

$integration = IntegrationTestBootstrap::boot($rootDir);
/** @var Explorer $database */
$database = $integration->database;
$pdo = $database->getConnection()->getPdo();

$reader = $integration->container->getByType(MessageTranslationReader::class);
$writer = $integration->container->getByType(MessageTranslationWriter::class);
$localeManager = $integration->container->getByType(LocaleManager::class);
$translatorFactory = $integration->container->getByType(TranslatorFactory::class);
$localeProvider = $integration->container->getByType(LocaleProvider::class);

$coreKey = 'button.add_to_cart';
$locale = 'cs';

$readCore = static function () use ($pdo, $coreKey, $locale): array {
    $statement = $pdo->prepare(
        'SELECT message_text, source, is_customized FROM catalog_message_translation WHERE message_key = ? AND locale = ?',
    );
    $statement->execute([$coreKey, $locale]);

    $row = $statement->fetch(PDO::FETCH_ASSOC);
    Assert::type('array', $row);

    return $row;
};

$original = $readCore();
Assert::same('core', $original['source']);
Assert::same(0, $original['is_customized']);

$viewerPermission = new class implements TranslationPermission {
    public function canView(): bool
    {
        return true;
    }

    public function canManage(): bool
    {
        return false;
    }
};

$viewerPresenter = new TestableTranslationsPresenter(
    $reader,
    $writer,
    $localeManager,
    $translatorFactory,
    $viewerPermission,
    $localeProvider,
);

$viewerForm = $viewerPresenter->manageForm();
$viewerSubmit = $viewerForm->onSuccess[0];

foreach (['update', 'delete', 'create'] as $operation) {
    Assert::exception(
        static fn () => $viewerSubmit($viewerForm, (object) [
            'operation' => $operation,
            'domain' => TranslationDomain::Catalog->value,
            'messageKey' => $coreKey,
            'locale' => $locale,
            'messageText' => 'Unauthorized change',
        ]),
        ForbiddenRequestException::class,
    );

    Assert::same($original, $readCore());
}

$managerPermission = new class implements TranslationPermission {
    public function canView(): bool
    {
        return true;
    }

    public function canManage(): bool
    {
        return true;
    }
};

$managerPresenter = new TestableTranslationsPresenter(
    $reader,
    $writer,
    $localeManager,
    $translatorFactory,
    $managerPermission,
    $localeProvider,
);

foreach (['update', 'delete'] as $operation) {
    $managerForm = $managerPresenter->manageForm();
    $managerSubmit = $managerForm->onSuccess[0];

    $managerSubmit($managerForm, (object) [
        'operation' => $operation,
        'domain' => TranslationDomain::Catalog->value,
        'messageKey' => $coreKey,
        'locale' => $locale,
        'messageText' => 'Unauthorized change',
    ]);

    Assert::true($managerForm->hasErrors(), 'Ani správce nesmí upravit ani odstranit systémový zdroj.');
    Assert::same($original, $readCore());
}

$deniedViewPermission = new class implements TranslationPermission {
    public function canView(): bool
    {
        return false;
    }

    public function canManage(): bool
    {
        return false;
    }
};

$deniedViewPresenter = new TestableTranslationsPresenter(
    $reader,
    $writer,
    $localeManager,
    $translatorFactory,
    $deniedViewPermission,
    $localeProvider,
);

Assert::exception(
    static fn () => $deniedViewPresenter->renderDefault(),
    ForbiddenRequestException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
