<?php

declare(strict_types=1);

use Nette\Forms\Form;
use Tester\Assert;

$rootDir = dirname(__DIR__, 3);
require $rootDir . '/vendor/autoload.php';

$form = new Form();
$form->addText('code');
$translations = $form->addContainer('translations');
$cs = $translations->addContainer('cs');
$cs->addText('name');
$cs->addTextArea('description');
$form->addMultiSelect('categoryIds', null, [1 => 'Category 1', 2 => 'Category 2']);

$form->setDefaults([
    'code' => 'nested-form-test',
    'translations' => [
        'cs' => [
            'name' => 'Testovací produkt',
            'description' => 'Popis produktu',
        ],
    ],
    'categoryIds' => ['1', '2'],
]);

$values = $form->getUntrustedValues('array');
Assert::true(is_array($values));
Assert::true(is_array($values['translations']));
Assert::true(is_array($values['translations']['cs']));
Assert::same('Testovací produkt', $values['translations']['cs']['name']);
Assert::same('Popis produktu', $values['translations']['cs']['description']);
Assert::same(['1', '2'], $values['categoryIds']);

echo "OK\n";
?>
--EXPECT--
OK
