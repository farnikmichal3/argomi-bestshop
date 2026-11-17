--TEST--
System message state resolver updates core defaults, preserves customized text and isolates custom keys
--FILE--
<?php

use App\Modules\Translation\Application\SystemMessageStateResolver;
use Tester\Assert;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';

$resolver = new SystemMessageStateResolver();
$oldDefault = 'Old default';
$newDefault = 'New default';
$oldHash = sha1($oldDefault);
$newHash = sha1($newDefault);

$updated = $resolver->resolve(
    $oldDefault,
    'core',
    false,
    $oldHash,
    false,
    $newDefault,
);

Assert::same($newDefault, $updated->messageText);
Assert::same('core', $updated->source);
Assert::false($updated->isCustomized);
Assert::same($newHash, $updated->defaultHash);
Assert::false($updated->isOutdated);

$unchanged = $resolver->resolve(
    $oldDefault,
    'core',
    false,
    $oldHash,
    false,
    $oldDefault,
);
Assert::same($oldDefault, $unchanged->messageText);
Assert::same($oldHash, $unchanged->defaultHash);
Assert::false($unchanged->isOutdated);

$customized = $resolver->resolve(
    'My own wording',
    'core',
    true,
    $oldHash,
    false,
    $newDefault,
);
Assert::same('My own wording', $customized->messageText);
Assert::true($customized->isCustomized);
Assert::same($newHash, $customized->defaultHash);
Assert::true($customized->isOutdated);

$customizedUnchanged = $resolver->resolve(
    'My own wording',
    'core',
    true,
    $oldHash,
    false,
    $oldDefault,
);
Assert::same('My own wording', $customizedUnchanged->messageText);
Assert::same($oldHash, $customizedUnchanged->defaultHash);
Assert::false($customizedUnchanged->isOutdated);

$customKey = $resolver->resolve(
    'My custom key',
    'custom',
    true,
    null,
    false,
    'Ignored default',
);
Assert::same('My custom key', $customKey->messageText);
Assert::same('custom', $customKey->source);
Assert::null($customKey->defaultHash);
Assert::false($customKey->isOutdated);

$legacyNullHash = $resolver->resolve(
    'Legacy text',
    'core',
    false,
    null,
    false,
    'New source',
);
Assert::same('Legacy text', $legacyNullHash->messageText);
Assert::null($legacyNullHash->defaultHash);
Assert::false($legacyNullHash->isOutdated);

Assert::exception(
    static fn () => $resolver->resolve('x', 'unknown', false, null, false, 'y'),
    InvalidArgumentException::class,
);

echo "OK\n";
?>
--EXPECT--
OK
