<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Bootstrap;
use Nette\Database\Explorer;
use Nette\DI\Container;
use Nette\Neon\Neon;
use RuntimeException;
use Tester\Environment;

/**
 * Společný kontext bezpečně připojeného MySQL integračního testu.
 */
final readonly class IntegrationTestContext
{
    public function __construct(
        public Container $container,
        public Explorer $database,
    ) {}
}

/**
 * Připraví Nette kontejner a ověří, že test míří výhradně do vyhrazené DB.
 */
final class IntegrationTestBootstrap
{
    private const DATABASE_NAME = 'argomi_bestshop_test';

    private const DATABASE_USERS = [
        'argomi_test_app@localhost',
        'argomi_test_app@127.0.0.1',
    ];

    private const CI_DATABASE_USER = 'argomi_test_app@%';


    public static function boot(string $rootDir): IntegrationTestContext
    {
        $configFile = $rootDir . '/config/local.testing.neon';

        if (!is_file($configFile)) {
            if (self::isDatabaseRequired()) {
                throw new RuntimeException(
                    'MySQL integration tests require config/local.testing.neon in CI.',
                );
            }

            Environment::skip('Create config/local.testing.neon to run MySQL integration tests.');
        }

        $configuration = Neon::decodeFile($configFile);
        $parameters = is_array($configuration) ? ($configuration['parameters'] ?? null) : null;
        $appParameters = is_array($parameters) ? ($parameters['app'] ?? null) : null;
        $environment = is_array($appParameters) ? ($appParameters['environment'] ?? null) : null;

        if ($environment !== 'testing') {
            throw new RuntimeException(
                'MySQL integration tests require the explicit app.environment=testing setting.',
            );
        }

        $container = (new Bootstrap('local.testing.neon'))->bootWebApplication();
        $database = $container->getByType(Explorer::class);

        $identity = $database->getConnection()->getPdo()
            ->query('SELECT DATABASE() AS database_name, CURRENT_USER() AS database_user')
            ->fetch(\PDO::FETCH_ASSOC);

        if (
            !is_array($identity)
            || ($identity['database_name'] ?? null) !== self::DATABASE_NAME
            || !self::isAllowedDatabaseUser($identity['database_user'] ?? null)
        ) {
            throw new RuntimeException(
                'MySQL integration tests may run only against argomi_bestshop_test using argomi_test_app.',
            );
        }

        return new IntegrationTestContext($container, $database);
    }


    /**
     * Lokální účty jsou omezené na loopback; GitHub Actions používá
     * dočasný MySQL service container a účet omezený životností tohoto běhu.
     */
    private static function isAllowedDatabaseUser(mixed $databaseUser): bool
    {
        if (in_array($databaseUser, self::DATABASE_USERS, true)) {
            return true;
        }

        return getenv('GITHUB_ACTIONS') === 'true'
            && $databaseUser === self::CI_DATABASE_USER;
    }


    private static function isDatabaseRequired(): bool
    {
        // CI začne vyžadovat DB až po přidání MySQL service do workflow F3-15c.
        // Obecné CI=true nestačí, protože ostatní workflow zatím MySQL nepřipravují.
        return getenv('TEST_DATABASE_REQUIRED') === '1';
    }
}
