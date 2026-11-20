--TEST--
Database tables use the approved primary-key and foreign-key naming conventions
--FILE--
<?php

use Nette\Database\Explorer;
use Tester\Assert;
use Tests\Support\IntegrationTestBootstrap;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require_once $rootDir . '/tests/Support/IntegrationTestBootstrap.php';

$integration = IntegrationTestBootstrap::boot($rootDir);
$container = $integration->container;

/** @var Explorer $database */
$database = $integration->database;
$pdo = $database->getConnection()->getPdo();

// Načteme skutečné tabulky databáze, nikoli seznam odvozený z migračních souborů.
$tables = $pdo->query(
    "SELECT TABLE_NAME
     FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_TYPE = 'BASE TABLE'
     ORDER BY TABLE_NAME",
)->fetchAll(PDO::FETCH_COLUMN);

$columns = $pdo->query(
    "SELECT TABLE_NAME, COLUMN_NAME
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     ORDER BY TABLE_NAME, ORDINAL_POSITION",
)->fetchAll(PDO::FETCH_ASSOC);

$columnsByTable = [];

foreach ($columns as $column) {
    $columnsByTable[$column['TABLE_NAME']][$column['COLUMN_NAME']] = true;
}

$primaryKeyRows = $pdo->query(
    "SELECT TABLE_NAME, COLUMN_NAME
     FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE()
       AND CONSTRAINT_NAME = 'PRIMARY'
     ORDER BY TABLE_NAME, ORDINAL_POSITION",
)->fetchAll(PDO::FETCH_ASSOC);

$primaryKeys = [];

foreach ($primaryKeyRows as $row) {
    $primaryKeys[$row['TABLE_NAME']][] = $row['COLUMN_NAME'];
}

// Přirozený klíč ISO 4217 je jediná schválená výjimka z konvence primárního klíče id.
$naturalPrimaryKeyExceptions = [
    'iso4217_currency' => ['code'],
];

Assert::true($tables !== [], 'Testovací databáze musí obsahovat tabulky.');

foreach ($tables as $table) {
    Assert::true(
        isset($columnsByTable[$table]['id']) || isset($naturalPrimaryKeyExceptions[$table]),
        sprintf('Tabulka %s nemá sloupec id a není schválenou výjimkou.', $table),
    );

    $expectedPrimaryKey = $naturalPrimaryKeyExceptions[$table] ?? ['id'];
    Assert::same(
        $expectedPrimaryKey,
        $primaryKeys[$table] ?? [],
        sprintf('Tabulka %s nemá očekávaný primární klíč.', $table),
    );
}

Assert::true(
    in_array('iso4217_currency', $tables, true),
    'ISO 4217 katalog musí být součástí testovacího schématu.',
);
Assert::false(
    isset($columnsByTable['iso4217_currency']['id']),
    'Tabulka iso4217_currency nesmí mít umělý sloupec id.',
);

// FK seskupujeme podle tabulky a názvu constraintu. Tím zachováme pořadí
// sloupců složených vazeb a nejsme závislí na náhodném pořadí řádků metadata dotazu.
$foreignKeyRows = $pdo->query(
    "SELECT
         TABLE_NAME,
         CONSTRAINT_NAME,
         COLUMN_NAME,
         REFERENCED_TABLE_NAME,
         REFERENCED_COLUMN_NAME,
         ORDINAL_POSITION
     FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE()
       AND REFERENCED_TABLE_NAME IS NOT NULL
     ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION",
)->fetchAll(PDO::FETCH_ASSOC);

$foreignKeys = [];

foreach ($foreignKeyRows as $row) {
    $key = $row['TABLE_NAME'] . '.' . $row['CONSTRAINT_NAME'];
    $foreignKeys[$key]['table'] = $row['TABLE_NAME'];
    $foreignKeys[$key]['source'][] = $row['COLUMN_NAME'];
    $foreignKeys[$key]['targetTable'] = $row['REFERENCED_TABLE_NAME'];
    $foreignKeys[$key]['target'][] = $row['REFERENCED_COLUMN_NAME'];
}

$expectedExceptions = [
    'category|parent_id|category|id',
    'product_relation|related_product_id|product|id',
    'variant_price|currency|currency|code',
    'variant_price_history|currency|currency|code',
    'option_value_price|currency|currency|code',
    'shipping_method_price|currency|currency|code',
    'cart|currency|currency|code',
    'shop_order|currency|currency|code',
    'attribute_translation|locale|language|code',
    'attribute_value_translation|locale|language|code',
    'admin_message_translation|locale|language|code',
    'cart|locale|language|code',
    'category_translation|locale|language|code',
    'customer|locale|language|code',
    'payment_method_translation|locale|language|code',
    'product_option_translation|locale|language|code',
    'product_option_value_translation|locale|language|code',
    'product_translation|locale|language|code',
    'shipping_method_translation|locale|language|code',
    'shop_order|locale|language|code',
    'cart_message_translation|locale|language|code',
    'catalog_message_translation|locale|language|code',
    'checkout_message_translation|locale|language|code',
    'unit_translation|locale|language|code',
    'currency|code,minor_units|iso4217_currency|code,minor_units',
    'product_variant_attribute_value|product_id,attribute_id|product_variant_axis|product_id,attribute_id',
];

$actualExceptions = [];

foreach ($foreignKeys as $foreignKey) {
    $sourceTable = $foreignKey['table'];
    $sourceColumns = $foreignKey['source'];
    $targetTable = $foreignKey['targetTable'];
    $targetColumns = $foreignKey['target'];

    Assert::same(
        count($sourceColumns),
        count($targetColumns),
        sprintf('Počet sloupců FK %s → %s nesouhlasí.', $sourceTable, $targetTable),
    );

    $signature = implode('|', [
        $sourceTable,
        implode(',', $sourceColumns),
        $targetTable,
        implode(',', $targetColumns),
    ]);

    if ($targetTable === 'iso4217_currency') {
        Assert::same(
            'currency|code,minor_units|iso4217_currency|code,minor_units',
            $signature,
            'Jediná povolená vazba na ISO katalog musí kontrolovat kód i minor_units.',
        );
        $actualExceptions[] = $signature;
        continue;
    }

    if ($targetColumns[0] !== 'id') {
        // Kódy měny a jazyka jsou samostatné přirozené klíče. Ostatní
        // povolené alternativní vazby musí přesně kopírovat složený cílový klíč.
        $isCurrencyCodeReference = $targetTable === 'currency'
            && $sourceColumns === ['currency']
            && $targetColumns === ['code'];
        $isLanguageCodeReference = $targetTable === 'language'
            && $sourceColumns === ['locale']
            && $targetColumns === ['code'];
        $isMatchingCompositeReference = count($targetColumns) > 1
            && $sourceColumns === $targetColumns;

        Assert::true(
            $isCurrencyCodeReference || $isLanguageCodeReference || $isMatchingCompositeReference,
            sprintf('FK %s → %s neodpovídá žádné schválené přirozené/alternativní klíčové konvenci.', $sourceTable, $targetTable),
        );
        $actualExceptions[] = $signature;
        continue;
    }

    Assert::same(
        'id',
        $targetColumns[0],
        sprintf('FK %s → %s odkazuje na nepodporovaný přirozený klíč.', $sourceTable, $targetTable),
    );

    $expectedSourceColumn = $targetTable . '_id';

    if ($sourceColumns[0] !== $expectedSourceColumn) {
        // Dvě pojmenovací výjimky jsou záměrné a jejich úplný seznam se kontroluje níže.
        Assert::true(
            in_array($signature, [
                'category|parent_id|category|id',
                'product_relation|related_product_id|product|id',
            ], true),
            sprintf(
                'První sloupec FK %s → %s musí být pojmenován %s.',
                $sourceTable,
                $targetTable,
                $expectedSourceColumn,
            ),
        );
        $actualExceptions[] = $signature;
    }
}

sort($expectedExceptions);
sort($actualExceptions);

Assert::same(
    $expectedExceptions,
    $actualExceptions,
    'Skutečné výjimky FK musí přesně odpovídat zdokumentovanému allowlistu; nové odchylky vyžadují rozhodnutí a dokumentaci.',
);

echo "OK\n";
?>
--EXPECT--
OK
