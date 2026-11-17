--TEST--
Translation key usage scanner resolves presenter domains and finds literal Latte and PHP keys
--FILE--
<?php

use App\Modules\Translation\Infrastructure\Diagnostics\TranslationKeyUsageScanner;
use Tester\Assert;

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'argomi-translation-scan-' . bin2hex(random_bytes(6));
$home = $root . DIRECTORY_SEPARATOR . 'Front' . DIRECTORY_SEPARATOR . 'Home';

mkdir($home, 0777, true);

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
