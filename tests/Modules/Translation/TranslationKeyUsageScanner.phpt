--TEST--
Translation key usage scanner resolves presenter domains and finds literal Latte and PHP keys
--FILE--
<?php

use App\Modules\Translation\Infrastructure\Diagnostics\TranslationKeyUsageScanner;
use Tester\Assert;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'argomi-translation-scan-' . bin2hex(random_bytes(6));
$home = $root . DIRECTORY_SEPARATOR . 'Front' . DIRECTORY_SEPARATOR . 'Home';

mkdir($home, 0777, true);

$admin = $root . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'Translations';
mkdir($admin, 0777, true);

file_put_contents(
    $home . DIRECTORY_SEPARATOR . 'HomePresenter.php',
    <<<'PHP'
<?php

use App\Modules\Translation\Api\TranslationDomain;
use App\Presentation\TranslatedPresenter;

final class HomePresenter extends TranslatedPresenter
{
    protected function getTranslationDomain(): TranslationDomain
    {
        return TranslationDomain::Catalog;
    }
}
PHP,
);

file_put_contents(
    $admin . DIRECTORY_SEPARATOR . 'TranslationsPresenter.php',
    <<<'PHP'
<?php

use App\Modules\\Translation\\Api\\TranslationDomain;
use App\Presentation\\TranslatedPresenter;

final class TranslationsPresenter extends TranslatedPresenter
{
    protected function getTranslationDomain(): TranslationDomain
    {
        return TranslationDomain::Admin;
    }
}
PHP,
);

file_put_contents(
    $admin . DIRECTORY_SEPARATOR . 'TranslationsPresenterExtra.php',
    <<<'PHP'
<?php
$form->addText('messageKey', 'translations.create.key');
$flashKey = 'translations.created';
$options = ['create' => 'translations.operation.create'];
PHP,
);

file_put_contents(
    $home . DIRECTORY_SEPARATOR . 'default.latte',
    <<<'LATTE'
<h1>{_'catalog.title'}</h1>
<p>{_'catalog.title'}</p>
<p>{_'catalog.products_count', count: $count}</p>
LATTE,
);

file_put_contents(
    $home . DIRECTORY_SEPARATOR . 'HomePresenterExtra.php',
    <<<'PHP'
<?php
$translator->translate('catalog.from_php');
PHP,
);

file_put_contents(
    $root . DIRECTORY_SEPARATOR . 'Front' . DIRECTORY_SEPARATOR . 'unowned.latte',
    <<<'LATTE'
<p>{_'not.owned'}</p>
LATTE,
);

$scanner = new TranslationKeyUsageScanner($root . DIRECTORY_SEPARATOR . 'Front');
$usages = $scanner->scan();

Assert::same([
    'admin' => [
        'translations.create.key' => true,
        'translations.created' => true,
        'translations.operation.create' => true,
    ],
    'catalog' => [
        'catalog.from_php' => true,
        'catalog.products_count' => true,
        'catalog.title' => true,
    ],
], $usages);

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST,
);

foreach ($iterator as $item) {
    if ($item->isDir()) {
        rmdir($item->getPathname());
    } else {
        unlink($item->getPathname());
    }
}

rmdir($root);

echo "OK\n";
?>
--EXPECT--
OK
