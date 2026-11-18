<?php

declare(strict_types=1);

namespace App;

use App\Core\Environment\DebugModePolicy;
use App\Core\Module\ModuleRegistry;
use App\Modules\Translation\Infrastructure\Diagnostics\MissingTranslationPanel;
use LogicException;
use Nette;
use Nette\Bootstrap\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\Neon\Neon;
use Tracy\Debugger;

class Bootstrap
{
    private readonly Configurator $configurator;
    private readonly string $rootDir;
    private readonly DebugModePolicy $debugModePolicy;


    public function __construct(
        private readonly string $localConfigFile = 'local.neon',
    ) {
        if (basename($localConfigFile) !== $localConfigFile || !str_ends_with($localConfigFile, '.neon')) {
            throw new LogicException('Local configuration must be a NEON file name without a directory path.');
        }

        $this->rootDir = dirname(__DIR__);
        $this->configurator = new Configurator();
        $this->debugModePolicy = new DebugModePolicy();
        $this->configurator->setTempDirectory($this->rootDir . '/temp');
    }


    public function bootWebApplication(): Nette\DI\Container
    {
        $this->initializeEnvironment();
        $this->setupContainer();
        $container = $this->configurator->createContainer();
        Debugger::getBar()->addPanel($container->getByType(MissingTranslationPanel::class));

        return $container;
    }


    public function initializeEnvironment(): void
    {
        $configDir = $this->rootDir . '/config';
        $localConfig = Neon::decodeFile($configDir . '/' . $this->localConfigFile);
        $parameters = is_array($localConfig) ? ($localConfig['parameters'] ?? null) : null;
        $appParameters = is_array($parameters) ? ($parameters['app'] ?? null) : null;
        $environment = is_array($appParameters) ? ($appParameters['environment'] ?? null) : null;

        // Vývojový režim nesmí určovat IP adresa ani automatická detekce Nette.
        // Povolen je pouze explicitní app.environment: development v lokální konfiguraci.
        $this->configurator->setDebugMode($this->debugModePolicy->isEnabled($environment));

        $logDir = $this->rootDir . '/log';
        if (!is_dir($logDir) && !mkdir($logDir, 0775, true) && !is_dir($logDir)) {
            throw new LogicException(sprintf('Unable to create Tracy logging directory "%s".', $logDir));
        }

        $this->configurator->enableTracy($logDir);

        $this->configurator->createRobotLoader()
            ->addDirectory(__DIR__)
            ->register();
    }


    private function setupContainer(): void
    {
        $configDir = $this->rootDir . '/config';
        $this->configurator->addConfig($configDir . '/common.neon');
        $this->configurator->addConfig($configDir . '/services.neon');
        $this->configurator->addConfig($configDir . '/' . $this->localConfigFile);

        $this->configurator->onCompile[] = static function (Configurator $configurator, Compiler $compiler): void {
            foreach (ModuleRegistry::MODULES as $module) {
                $extensionClass = 'App\\Modules\\' . $module . '\\Infrastructure\\DI\\' . $module . 'Extension';

                if (!class_exists($extensionClass)) {
                    continue;
                }

                if (!is_subclass_of($extensionClass, CompilerExtension::class)) {
                    throw new LogicException(sprintf('Invalid DI extension for module %s.', $module));
                }

                $extension = new $extensionClass();
                $compiler->addExtension('module_' . strtolower($module), $extension);
            }
        };
    }
}
