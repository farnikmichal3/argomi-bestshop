<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Console;

use App\Modules\Identity\Application\AdminAuthenticationRepository;
use Nette\Database\UniqueConstraintViolationException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'identity:admin:create',
    description: 'Bezpečně vytvoří první nebo další administrátorský účet',
)]
final class CreateAdminAccountCommand extends Command
{
    public function __construct(
        private readonly AdminAuthenticationRepository $repository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $emailInput = $io->ask('E-mail administrátora');

        if (!is_string($emailInput)) {
            $io->error('E-mail není platný.');

            return Command::FAILURE;
        }

        $email = mb_strtolower(trim($emailInput));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 254) {
            $io->error('Zadaný e-mail není platný.');

            return Command::FAILURE;
        }

        $password = $io->askHidden('Heslo (minimálně 14 znaků)');
        $confirmation = $io->askHidden('Zopakuj heslo');

        if (
            !is_string($password)
            || !is_string($confirmation)
            || mb_strlen($password) < 14
            || !hash_equals($password, $confirmation)
        ) {
            $io->error('Hesla nesouhlasí nebo nesplňují minimální délku 14 znaků.');

            return Command::FAILURE;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if (!is_string($passwordHash)) {
            $io->error('Bezpečné uložení hesla se nezdařilo.');

            return Command::FAILURE;
        }

        try {
            $adminId = $this->repository->createAdministrator($email, $passwordHash);
        } catch (UniqueConstraintViolationException) {
            $io->error('Administrátor s tímto e-mailem již existuje.');

            return Command::FAILURE;
        }

        $io->success(sprintf('Administrátorský účet byl vytvořen. ID: %d.', $adminId));

        return Command::SUCCESS;
    }
}
