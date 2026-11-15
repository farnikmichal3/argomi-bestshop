--TEST--
DbResetCommand rejects unexpected database object types before changing the database
--FILE--
<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Console\DbResetCommand;
use App\Shared\Infrastructure\Console\DbResetEnvironmentPolicy;
use Nextras\Migrations\Entities\File;
use Nextras\Migrations\Entities\Migration;
use Nextras\Migrations\IDbal;
use Nextras\Migrations\IConfiguration;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\Printers\Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tester\Assert;

require __DIR__ . '/../../../vendor/autoload.php';

/** @var list<string> $executedStatements */
$executedStatements = [];
/** @var list<string> $escapedIdentifiers */
$escapedIdentifiers = [];

$dbal = new class($executedStatements, $escapedIdentifiers) implements IDbal {
    /** @var list<list<array<string, mixed>>> */
    private array $responses;

    /** @var list<string> */
    public array $queries = [];

    /** @var list<string> */
    public array $executedStatements = [];

    /** @var list<string> */
    public array $escapedIdentifiers = [];

    /**
     * @param list<string> $executedStatements
     * @param list<string> $escapedIdentifiers
     */
    public function __construct(array &$executedStatements, array &$escapedIdentifiers)
    {
        $this->executedStatements =& $executedStatements;
        $this->escapedIdentifiers =& $escapedIdentifiers;
        $this->responses = [
            [[
                'database_name' => 'argomi_bestshop',
                'database_user' => 'argomi_migrator@localhost',
            ]],
            [[
                'table_name' => 'unexpected_object',
                'table_type' => 'PROCEDURE',
            ]],
        ];
    }

    public function query(string $sql): array
    {
        $this->queries[] = $sql;

        return array_shift($this->responses) ?? [];
    }

    public function exec(string $sql): int
    {
        $this->executedStatements[] = $sql;

        return 0;
    }

    public function escapeString(string $value): string
    {
        return $value;
    }

    public function escapeInt(int $value): string
    {
        return (string) $value;
    }

    public function escapeBool(bool $value): string
    {
        return $value ? '1' : '0';
    }

    public function escapeDateTime(DateTimeInterface $value): string
    {
        return $value->format('Y-m-d H:i:s');
    }

    public function escapeIdentifier(string $value): string
    {
        $this->escapedIdentifiers[] = $value;

        return sprintf('`%s`', $value);
    }
};

/** @var IDriver $driver */
$driver = new class implements IDriver {
    public function setupConnection(): void {}
    public function emptyDatabase(): void {}
    public function loadFile(string $path): int { return 0; }
    public function beginTransaction(): void {}
    public function commitTransaction(): void {}
    public function rollbackTransaction(): void {}
    public function lock(): void {}
    public function unlock(): void {}
    public function createTable(): void {}
    public function dropTable(): void {}
    public function insertMigration(Migration $migration): void {}
    public function markMigrationAsReady(Migration $migration): void {}
    public function getAllMigrations(): array { return []; }
    public function getInitTableSource(): string { return ''; }
    public function getInitMigrationsSource(array $files): string { return ''; }
};

/** @var IConfiguration $configuration */
$configuration = new class implements IConfiguration {
    public function getGroups(): array { return []; }
    public function getExtensionHandlers(): array { return []; }
};

$command = new DbResetCommand(
    $driver,
    $configuration,
    $dbal,
    new Console(),
    new DbResetEnvironmentPolicy('development'),
);

$tester = new CommandTester($command);
$tester->setInputs(['yes', 'argomi_bestshop']);
$exitCode = $tester->execute([]);

Assert::same(Command::FAILURE, $exitCode);
Assert::same(2, count($dbal->queries));
Assert::same([], $executedStatements);
Assert::same([], $escapedIdentifiers);
Assert::contains('neočekávaný typ', $tester->getDisplay());

echo "OK\n";
?>
--EXPECT--
OK
