--TEST--
Missing translations are logged once per request and safely displayed in the Tracy panel
--FILE--
<?php

use App\Modules\Translation\Application\Dto\MessageCatalog;
use App\Modules\Translation\Domain\MessageDomain;
use App\Modules\Translation\Infrastructure\Diagnostics\MissingTranslationDiagnostics;
use App\Modules\Translation\Infrastructure\Diagnostics\MissingTranslationPanel;
use App\Modules\Translation\Infrastructure\Translation\DatabaseTranslator;
use App\Shared\Domain\Locale\Locale;
use Psr\Log\NullLogger;
use Tester\Assert;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$diagnostics = new MissingTranslationDiagnostics(new NullLogger());
$locale = Locale::fromString('en');
$catalog = new MessageCatalog(MessageDomain::Catalog, $locale, []);
$fallback = new MessageCatalog(
    MessageDomain::Catalog,
    Locale::fromString('cs'),
    ['catalog.fallback' => 'Výchozí text'],
);
$translator = new DatabaseTranslator($catalog, $fallback, $diagnostics);

Assert::same('Výchozí text', $translator->translate('catalog.fallback'));
Assert::same('catalog.missing<script>', $translator->translate('catalog.missing<script>'));
Assert::same(2, count($diagnostics->getMissing()));

$diagnostics->report(MessageDomain::Catalog, $locale, 'catalog.fallback');
Assert::same(2, count($diagnostics->getMissing()));

$panel = new MissingTranslationPanel($diagnostics);
Assert::contains('Translations: 2', $panel->getTab());
Assert::contains('&lt;script&gt;', $panel->getPanel());
Assert::notContains('<script>', $panel->getPanel());

echo "OK\n";
?>
--EXPECT--
OK
