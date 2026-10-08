<?php

declare(strict_types=1);

namespace App\Console;

use App\Blog\CategoryRepository;
use App\Blog\PostRepository;
use App\Blog\PostStatus;
use App\Shared\Slugger;
use App\User\Role;
use App\User\UserRepository;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Security\PasswordHasher;

/**
 * Popula o banco com o autor (admin), as categorias e os artigos de `seed/posts/*.md`.
 *
 * Cada artigo é um arquivo Markdown com front matter:
 *
 *     ---
 *     title: Título do artigo
 *     excerpt: Resumo exibido nos cards
 *     category: Yii3
 *     cover: /covers/titulo-do-artigo.webp   (opcional)
 *     days_ago: 10
 *     ---
 *     Conteúdo em Markdown...
 */
#[AsCommand(name: 'app:seed', description: 'Popula o banco com o autor, categorias e artigos de seed/posts (somente se estiver vazio).')]
final class SeedCommand extends Command
{
    private const AUTHOR = [
        'name' => 'Yuri Neves',
        'email' => 'admin@miniblog.test',
        'password' => 'admin123',
        'bio' => 'Desenvolvedor PHP em Curitiba. Back-end, APIs e, agora, Yii3.',
    ];

    private const CATEGORIES = [
        'Yii3' => 'Como o Yii3 funciona hoje: pacotes, PSRs, configuração e o caminho de uma requisição.',
        'Bastidores' => 'Como este blog foi construído, decisão por decisão: arquitetura, código e erros pelo caminho.',
    ];

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly UserRepository $users,
        private readonly CategoryRepository $categories,
        private readonly PostRepository $posts,
        private readonly PasswordHasher $passwordHasher,
        private readonly Aliases $aliases,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('fresh', null, InputOption::VALUE_NONE, 'Apaga TODOS os dados (posts, categorias, usuários, tokens e configurações) antes de popular.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('fresh')) {
            if (!$io->confirm('Apagar todos os dados do banco e recriar?', !$input->isInteractive())) {
                return Command::SUCCESS;
            }
            $this->wipe();
            $io->writeln('Dados apagados.');
        } elseif ($this->users->count() > 0) {
            $io->writeln('Banco já possui usuários — seed ignorado. Use --fresh para recriar.');
            return Command::SUCCESS;
        }

        $articles = $this->loadArticles();

        $this->db->transaction(function () use ($articles): void {
            $authorId = $this->users->insert([
                'name' => self::AUTHOR['name'],
                'email' => self::AUTHOR['email'],
                'password_hash' => $this->passwordHasher->hash(self::AUTHOR['password']),
                'role' => Role::Admin->value,
                'bio' => self::AUTHOR['bio'],
                'is_active' => true,
            ]);

            $categoryIds = [];
            foreach (self::CATEGORIES as $name => $description) {
                $categoryIds[$name] = $this->categories->insert([
                    'name' => $name,
                    'slug' => Slugger::slugify($name),
                    'description' => $description,
                ]);
            }

            foreach ($articles as $i => $article) {
                if (!isset($categoryIds[$article['category']])) {
                    throw new RuntimeException("Categoria desconhecida \"{$article['category']}\" em {$article['file']}.");
                }

                $date = date('Y-m-d H:i:s', strtotime("-{$article['days_ago']} days 09:30") - $i * 60);
                $id = $this->posts->insert([
                    'title' => $article['title'],
                    'slug' => Slugger::slugify($article['title']),
                    'excerpt' => $article['excerpt'],
                    'cover_url' => $article['cover'],
                    'content' => $article['content'],
                    'category_id' => $categoryIds[$article['category']],
                    'author_id' => $authorId,
                    'reviewer_id' => $authorId,
                    'status' => PostStatus::Published->value,
                    'views' => 0,
                    'submitted_at' => $date,
                    'published_at' => $date,
                ]);
                $this->db->createCommand()
                    ->update('post', ['created_at' => $date, 'updated_at' => $date], ['id' => $id])
                    ->execute();
            }
        });

        $io->success(sprintf('Seed concluído: 1 usuário, %d categorias e %d artigos.', count(self::CATEGORIES), count($articles)));
        $io->table(['Papel', 'E-mail', 'Senha'], [[Role::Admin->label(), self::AUTHOR['email'], self::AUTHOR['password']]]);

        return Command::SUCCESS;
    }

    /**
     * @return list<array{file: string, title: string, excerpt: string, category: string, cover: ?string, days_ago: int, content: string}>
     */
    private function loadArticles(): array
    {
        $files = glob($this->aliases->get('@root/seed/posts') . '/*.md') ?: [];
        sort($files);

        $articles = [];
        foreach ($files as $file) {
            $raw = str_replace("\r\n", "\n", (string) file_get_contents($file));
            if (!preg_match('/\A---\n(.*?)\n---\n(.*)\z/s', $raw, $m)) {
                throw new RuntimeException('Front matter ausente em ' . basename($file));
            }

            $meta = [];
            foreach (explode("\n", $m[1]) as $line) {
                if (str_contains($line, ':')) {
                    [$key, $value] = explode(':', $line, 2);
                    $meta[trim($key)] = trim($value);
                }
            }

            $articles[] = [
                'file' => basename($file),
                'title' => $meta['title'] ?? basename($file, '.md'),
                'excerpt' => $meta['excerpt'] ?? '',
                'category' => $meta['category'] ?? '',
                'cover' => ($meta['cover'] ?? '') !== '' ? $meta['cover'] : null,
                'days_ago' => (int) ($meta['days_ago'] ?? 0),
                'content' => trim($m[2]) . "\n",
            ];
        }

        return $articles;
    }

    private function wipe(): void
    {
        $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
        foreach (['api_token', 'post', 'category', 'setting', 'user'] as $table) {
            $this->db->createCommand()->truncateTable($table)->execute();
        }
        $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
    }
}
