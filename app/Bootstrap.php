<?php

declare(strict_types=1);

namespace App;

use App\Core\Module\ModuleRegistry;
use LogicException;
use Nette;
use Nette\Bootstrap\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;

class Bootstrap
{
    private readonly Configurator $configurator;
    private readonly string $rootDir;


    public function __construct()
    {
        $this->rootDir = dirname(__DIR__);
        $this->configurator = new Configurator();
        $this->configurator->setTempDirectory($this->rootDir . '/temp');
    }


    public function bootWebApplication(): Nette\DI\Container
    {
        $this->initializeEnvironment();
        $this->setupContainer();
        return $this->configurator->createContainer();
    }


    public function initializeEnvironment(): void
    {
        //$this->configurator->setDebugMode('secret@23.75.345.200'); // enable for your remote IP
        $this->configurator->enableTracy($this->rootDir . '/log');

        $this->configurator->createRobotLoader()
            ->addDirectory(__DIR__)
            ->register();
    }


    private function setupContainer(): void
    {
        $configDir = $this->rootDir . '/config';
        $this->configurator->addConfig($configDir . '/common.neon');
        $this->configurator->addConfig($configDir . '/services.neon');
        $this->configurator->addConfig($configDir . '/local.neon');

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
