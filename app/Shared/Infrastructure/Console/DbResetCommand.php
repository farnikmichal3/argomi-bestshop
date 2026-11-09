<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use Nextras\Migrations\Bridges\SymfonyConsole\BaseCommand;
use Nextras\Migrations\Engine\Runner;
use Nextras\Migrations\IDbal;
use Nextras\Migrations\IConfiguration;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\IPrinter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'db:reset',
    description: 'Bezpečně resetuje lokální vývojovou databázi a znovu spustí migrace',
)]
final class DbResetCommand extends BaseCommand
{
    private const DATABASE_NAME = 'argomi_bestshop';

    private const ALLOWED_DATABASE_USERS = [
        'argomi_migrator@localhost',
        'argomi_migrator@127.0.0.1',
    ];

    public function __construct(
        IDriver $driver,
        IConfiguration $config,
        private readonly IDbal $dbal,
        ?IPrinter $printer = null,
    ) {
        parent::__construct($driver, $config, $printer);
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Destruktivní příkaz určený výhradně pro lokální vývojovou databázi argomi_bestshop. '
            . 'Ponechá databázi a její oprávnění, odstraní její tabulky a pohledy a znovu provede všechny migrace.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $identity = $this->dbal->query(
            'SELECT DATABASE() AS database_name, CURRENT_USER() AS database_user',
        )[0] ?? [];

        $databaseName = $identity['database_name'] ?? null;
        $databaseUser = $identity['database_user'] ?? null;

        if (
            $databaseName !== self::DATABASE_NAME
            || !in_array($databaseUser, self::ALLOWED_DATABASE_USERS, true)
        ) {
            $io->error(
                'Reset odmítnut. Příkaz je povolen pouze pro databázi argomi_bestshop '
                . 'a lokální účet argomi_migrator.',
            );

            return Command::FAILURE;
        }

        $io->warning(
            'Následující operace nevratně odstraní všechna data v databázi argomi_bestshop '
            . 'a znovu vytvoří schéma včetně základních a demo dat.',
        );

        if (!$io->confirm('Opravdu chceš pokračovat?', false)) {
            $io->comment('Reset byl zrušen.');

            return Command::SUCCESS;
        }

        $confirmation = $io->ask('Pro potvrzení napiš přesný název databáze', '');

        if ($confirmation !== self::DATABASE_NAME) {
            $io->error('Název databáze nesouhlasí. Reset nebyl proveden.');

            return Command::FAILURE;
        }

        $io->section('Odstraňuji tabulky a pohledy');

        $objects = $this->dbal->query(
            "SELECT TABLE_NAME AS table_name, TABLE_TYPE AS table_type
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_TYPE IN ('BASE TABLE', 'VIEW')
            ORDER BY CASE WHEN TABLE_TYPE = 'VIEW' THEN 0 ELSE 1 END, TABLE_NAME",
        );

        $this->dbal->exec('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($objects as $object) {
                $tableName = $object['table_name'] ?? null;
                $tableType = $object['table_type'] ?? null;

                if (!is_string($tableName) || !is_string($tableType)) {
                    throw new \RuntimeException('Databáze vrátila neočekávaný seznam objektů.');
                }

                $identifier = $this->dbal->escapeIdentifier($tableName);
                $statement = $tableType === 'VIEW'
                    ? "DROP VIEW IF EXISTS $identifier"
                    : "DROP TABLE IF EXISTS $identifier";

                $this->dbal->exec($statement);
            }
        } finally {
            $this->dbal->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        $io->success(sprintf('Odstraněno objektů: %d.', count($objects)));
        $io->section('Spouštím všechny databázové migrace');

        return $this->runMigrations(Runner::MODE_CONTINUE, $this->config);
    }
}
