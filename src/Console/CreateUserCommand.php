<?php

declare(strict_types=1);

namespace App\Console;

use App\User\Role;
use App\User\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Security\PasswordHasher;

#[AsCommand(name: 'user:create', description: 'Cria um usuário do painel (admin, reviewer ou editor).')]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'E-mail de acesso')
            ->addArgument('password', InputArgument::REQUIRED, 'Senha (mínimo 8 caracteres)')
            ->addArgument('role', InputArgument::OPTIONAL, 'admin | reviewer | editor', Role::Admin->value)
            ->addArgument('name', InputArgument::OPTIONAL, 'Nome de exibição', 'Administrador');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $password = (string) $input->getArgument('password');
        $role = Role::tryFrom((string) $input->getArgument('role'));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $io->error('E-mail inválido.');
            return Command::INVALID;
        }
        if (mb_strlen($password) < 8) {
            $io->error('A senha deve ter ao menos 8 caracteres.');
            return Command::INVALID;
        }
        if ($role === null) {
            $io->error('Papel inválido. Use: admin, reviewer ou editor.');
            return Command::INVALID;
        }
        if ($this->users->emailExists($email)) {
            $io->error('Já existe um usuário com este e-mail.');
            return Command::FAILURE;
        }

        $this->users->insert([
            'name' => (string) $input->getArgument('name'),
            'email' => $email,
            'password_hash' => $this->passwordHasher->hash($password),
            'role' => $role->value,
            'is_active' => true,
        ]);

        $io->success(sprintf('Usuário %s criado com o papel %s.', $email, $role->label()));
        return Command::SUCCESS;
    }
}
