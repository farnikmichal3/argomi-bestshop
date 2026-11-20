<?php

declare(strict_types=1);

namespace App\Modules\Translation\Infrastructure\Console;

use App\Modules\Translation\Infrastructure\Diagnostics\TranslationKeyUsageScanner;
use App\Shared\Domain\Locale\LocaleProvider;
use Nette\Database\Explorer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UnexpectedValueException;

#[AsCommand(
    name: 'translations:check',
    description: 'Zkontroluje chybějící a nepoužívané systémové překladové klíče',
)]
final class TranslationsCheckCommand extends Command
{
    /** @var array<string, string> */
    private const DOMAIN_TABLES = [
        'catalog' => 'catalog_message_translation',
        'cart' => 'cart_message_translation',
        'checkout' => 'checkout_message_translation',
        'admin' => 'admin_message_translation',
    ];

    public function __construct(
        private readonly Explorer $database,
        private readonly LocaleProvider $localeProvider,
        private readonly TranslationKeyUsageScanner $usageScanner,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $usages = $this->usageScanner->scan();
        $locales = $this->localeProvider->getActiveLocales();
        $storedKeys = [];

        foreach (self::DOMAIN_TABLES as $domain => $table) {
            foreach ($locales as $locale) {
                $rows = $this->database->table($table)
                    ->where('locale', $locale->getCode())
                    ->fetchAll();

                foreach ($rows as $row) {
                    $key = $row->message_key;

                    if (!is_string($key) || $key === '') {
                        throw new UnexpectedValueException(
                            sprintf('Tabulka "%s" obsahuje neplatný systémový překladový klíč.', $table),
                        );
                    }

                    $storedKeys[$domain][$key][$locale->getCode()] = true;
                }
            }
        }

        $missing = [];
        $unused = [];

        foreach ($usages as $domain => $keys) {
            foreach (array_keys($keys) as $key) {
                foreach ($locales as $locale) {
                    if (!isset($storedKeys[$domain][$key][$locale->getCode()])) {
                        $missing[] = [$domain, $key, $locale->getCode()];
                    }
                }
            }
        }

        foreach ($storedKeys as $domain => $keys) {
            foreach (array_keys($keys) as $key) {
                if (!isset($usages[$domain][$key])) {
                    $unused[] = [$domain, $key];
                }
            }
        }

        $io->title('Kontrola systémových překladů');

        if ($missing !== []) {
            $io->section(sprintf('Chybějící překlady (%d)', count($missing)));

            foreach ($missing as [$domain, $key, $locale]) {
                $io->text(sprintf('[%s] %s (%s)', $domain, $key, $locale));
            }
        }

        if ($unused !== []) {
            $io->section(sprintf('Nepoužívané klíče (%d)', count($unused)));

            foreach ($unused as [$domain, $key]) {
                $io->text(sprintf('[%s] %s', $domain, $key));
            }
        }

        if ($missing !== [] || $unused !== []) {
            $io->warning(sprintf(
                'Kontrola nalezla %d chybějících jazykových hodnot a %d nepoužívaných klíčů. Databáze nebyla změněna.',
                count($missing),
                count($unused),
            ));

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Všechny používané klíče mají překlad ve všech %d aktivních jazycích; nepoužívané klíče nebyly nalezeny.',
            count($locales),
        ));

        return Command::SUCCESS;
    }
}
