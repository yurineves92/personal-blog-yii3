<?php

declare(strict_types=1);

namespace App\Console;

use App\Blog\CategoryRepository;
use App\Blog\PostRepository;
use App\Blog\PostStatus;
use App\Shared\Slugger;
use App\User\Role;
use App\User\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Security\PasswordHasher;

#[AsCommand(name: 'app:seed', description: 'Popula o banco com usuários, categorias e posts de exemplo (somente se estiver vazio).')]
final class SeedCommand extends Command
{
    /** Usuários de demonstração: [nome, e-mail, senha, papel, bio] */
    private const USERS = [
        ['Ana Admin', 'admin@miniblog.test', 'admin123', Role::Admin, 'Cuida da plataforma e da equipe editorial.'],
        ['Rafael Revisor', 'revisor@miniblog.test', 'revisor123', Role::Reviewer, 'Revisor-chefe. Gosta de textos curtos e exemplos claros.'],
        ['Edu Editor', 'editor@miniblog.test', 'editor123', Role::Editor, 'Desenvolvedor PHP que escreve sobre o dia a dia.'],
        ['Marina Costa', 'marina@miniblog.test', 'marina123', Role::Editor, 'Product designer, escreve sobre produto e carreira.'],
    ];

    private const CATEGORIES = [
        ['Tecnologia', 'Arquitetura, ferramentas e boas práticas de desenvolvimento.'],
        ['Produto', 'Descoberta, priorização e construção de produtos digitais.'],
        ['Carreira', 'Crescimento profissional, comunicação e times.'],
        ['Tutoriais', 'Passo a passo prático para colocar a mão na massa.'],
    ];

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly UserRepository $users,
        private readonly CategoryRepository $categories,
        private readonly PostRepository $posts,
        private readonly PasswordHasher $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->users->count() > 0) {
            $io->writeln('Banco já possui usuários — seed ignorado.');
            return Command::SUCCESS;
        }

        $this->db->transaction(function () use ($io): void {
            $userIds = [];
            foreach (self::USERS as [$name, $email, $password, $role, $bio]) {
                $userIds[$role->value . ':' . $email] = $this->users->insert([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => $this->passwordHasher->hash($password),
                    'role' => $role->value,
                    'bio' => $bio,
                    'is_active' => true,
                ]);
            }
            [$admin, $reviewer, $editor, $marina] = array_values($userIds);

            $categoryIds = [];
            foreach (self::CATEGORIES as [$name, $description]) {
                $categoryIds[$name] = $this->categories->insert([
                    'name' => $name,
                    'slug' => Slugger::slugify($name),
                    'description' => $description,
                ]);
            }

            foreach ($this->samplePosts() as $i => $post) {
                $author = match ($post['author']) {
                    'admin' => $admin,
                    'reviewer' => $reviewer,
                    'marina' => $marina,
                    default => $editor,
                };
                $daysAgo = $post['daysAgo'];
                $date = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -" . (2 + $i) . ' hours'));
                $status = $post['status'];

                $id = $this->posts->insert([
                    'title' => $post['title'],
                    'slug' => Slugger::slugify($post['title']),
                    'excerpt' => $post['excerpt'],
                    'content' => $post['content'],
                    'category_id' => $categoryIds[$post['category']],
                    'author_id' => $author,
                    'status' => $status->value,
                    'reviewer_id' => in_array($status, [PostStatus::Published, PostStatus::Rejected], true)
                        ? ($author === $reviewer ? $admin : $reviewer)
                        : null,
                    'review_note' => $post['note'] ?? null,
                    'views' => $status === PostStatus::Published ? 40 + ($i * 37) % 400 : 0,
                    'submitted_at' => $status !== PostStatus::Draft ? $date : null,
                    'published_at' => $status === PostStatus::Published ? $date : null,
                ]);

                // Mantém datas de criação/atualização coerentes com a de publicação.
                $this->db->createCommand()
                    ->update('post', ['created_at' => $date, 'updated_at' => $date], ['id' => $id])
                    ->execute();
            }

            $io->success('Dados de exemplo criados.');
            $io->table(
                ['Papel', 'E-mail', 'Senha'],
                array_map(static fn(array $u) => [$u[3]->label(), $u[1], $u[2]], self::USERS),
            );
        });

        return Command::SUCCESS;
    }

    /**
     * @return list<array{title: string, excerpt: string, content: string, category: string, author: string, status: PostStatus, daysAgo: int, note?: string}>
     */
    private function samplePosts(): array
    {
        return [
            [
                'title' => 'Por que escolhemos o Yii3 para este blog',
                'excerpt' => 'Pacotes independentes, PSR em todo lugar e injeção de dependência de verdade. Um resumo do que nos convenceu.',
                'category' => 'Tecnologia',
                'author' => 'admin',
                'status' => PostStatus::Published,
                'daysAgo' => 2,
                'content' => <<<MD
                    O Yii3 não é uma simples atualização do Yii2: é uma reescrita completa, feita de **pacotes pequenos e independentes**.

                    ## O que mais gostamos

                    - **PSR em todo lugar**: requisições PSR-7, middlewares PSR-15 e container PSR-11.
                    - **Sem estado global**: nada de `Yii::\$app`. Tudo chega via construtor.
                    - **Configuração explícita**: o plugin `yiisoft/config` junta as configurações dos pacotes com as da aplicação.

                    ## Um exemplo de action

                    ```php
                    final readonly class Action
                    {
                        public function __construct(private WebViewRenderer \$viewRenderer) {}

                        public function __invoke(): ResponseInterface
                        {
                            return \$this->viewRenderer->render(__DIR__ . '/template');
                        }
                    }
                    ```

                    Cada página é uma classe pequena, fácil de testar e de entender.

                    > Menos mágica, mais clareza.
                    MD,
            ],
            [
                'title' => 'Docker, Nginx e PHP-FPM: o setup mínimo que funciona',
                'excerpt' => 'Três serviços, um docker-compose e nenhum segredo: veja como rodamos o Miniblog localmente.',
                'category' => 'Tutoriais',
                'author' => 'editor',
                'status' => PostStatus::Published,
                'daysAgo' => 5,
                'content' => <<<MD
                    Para desenvolvimento local, usamos três contêineres:

                    1. **nginx** — serve arquivos estáticos e repassa o resto para o PHP;
                    2. **php** — PHP-FPM com as extensões `pdo_mysql`, `intl` e `opcache`;
                    3. **mysql** — banco de dados com volume persistente.

                    ## O bloco que importa no Nginx

                    ```nginx
                    location / {
                        try_files \$uri \$uri/ /index.php\$is_args\$args;
                    }
                    ```

                    Qualquer URL que não seja um arquivo físico cai no `index.php`, onde o roteador do Yii assume.

                    ## Subindo tudo

                    ```bash
                    docker compose up -d --build
                    ```

                    O entrypoint do contêiner PHP instala as dependências, espera o MySQL, roda as migrations e popula o banco. Pronto.
                    MD,
            ],
            [
                'title' => 'Como priorizar o backlog sem planilhas infinitas',
                'excerpt' => 'Impacto, esforço e confiança: um jeito simples de decidir o que vem primeiro.',
                'category' => 'Produto',
                'author' => 'marina',
                'status' => PostStatus::Published,
                'daysAgo' => 8,
                'content' => <<<MD
                    Priorizar é escolher o que **não** fazer agora. Para isso, usamos três perguntas para cada item:

                    | Critério   | Pergunta                                       |
                    |------------|------------------------------------------------|
                    | Impacto    | Quantas pessoas isso ajuda, e quanto?          |
                    | Esforço    | Quantas semanas de time isso consome?          |
                    | Confiança  | Quanta evidência temos de que vai funcionar?   |

                    Dê uma nota de 1 a 5 para cada critério e ordene por `impacto × confiança ÷ esforço`.

                    ## O que a fórmula não resolve

                    Ela não substitui conversa. Use o número para **começar** a discussão, nunca para encerrá-la.
                    MD,
            ],
            [
                'title' => 'Feedback que ajuda: o papel do revisor',
                'excerpt' => 'Revisar não é corrigir vírgula. É garantir que o texto entrega o que promete.',
                'category' => 'Carreira',
                'author' => 'reviewer',
                'status' => PostStatus::Published,
                'daysAgo' => 11,
                'content' => <<<MD
                    No Miniblog, todo post passa por um revisor antes de ser publicado. Mas o que exatamente revisamos?

                    ## Nossa checklist

                    - O título promete algo que o texto entrega?
                    - Existe um exemplo concreto?
                    - Dá para cortar 20% sem perder nada?
                    - O leitor sabe o que fazer depois de ler?

                    ## Como devolver um texto

                    Quando um post volta para o autor, o comentário precisa ser **específico** e **acionável**. Em vez de "melhorar a introdução", prefira "a introdução demora três parágrafos para chegar ao ponto; comece pelo problema".
                    MD,
            ],
            [
                'title' => 'RBAC na prática: admin, editor e revisor',
                'excerpt' => 'Papéis, permissões e regras: como o painel decide quem pode fazer o quê.',
                'category' => 'Tecnologia',
                'author' => 'editor',
                'status' => PostStatus::Published,
                'daysAgo' => 15,
                'content' => <<<MD
                    O controle de acesso do painel usa o pacote `yiisoft/rbac`. Cada usuário tem um papel, e cada papel agrupa permissões.

                    ## Regras para "os próprios posts"

                    O editor tem a permissão `post.updateOwn`, que carrega uma **regra**: só vale se o usuário for o autor e o post estiver em rascunho ou rejeitado.

                    ```php
                    \$currentUser->can('post.update', ['post' => \$post]);
                    ```

                    A mesma chamada funciona para o revisor (que pode editar qualquer post) e para o editor (que passa pela regra).

                    ## Por que não um `if` no controller?

                    Porque a regra fica em **um só lugar**, e os templates podem usar a mesma verificação para esconder botões.
                    MD,
            ],
            [
                'title' => 'Escrevendo em Markdown sem sofrimento',
                'excerpt' => 'Os 10% da sintaxe que resolvem 90% dos textos.',
                'category' => 'Tutoriais',
                'author' => 'marina',
                'status' => PostStatus::Published,
                'daysAgo' => 19,
                'content' => <<<MD
                    Os posts deste blog são escritos em Markdown. Você só precisa de poucas coisas:

                    ## Títulos

                    Use `##` para seções e `###` para subseções.

                    ## Ênfase

                    `**negrito**` vira **negrito** e `_itálico_` vira _itálico_.

                    ## Links e imagens

                    ```markdown
                    [texto do link](https://exemplo.com)
                    ![descrição](https://exemplo.com/imagem.png)
                    ```

                    ## Citações

                    > Comece a linha com `>` para criar uma citação.

                    Pronto: com isso você escreve praticamente qualquer post.
                    MD,
            ],
            [
                'title' => 'Reuniões mais curtas com um documento de uma página',
                'excerpt' => 'Escrever antes de falar economiza horas por semana.',
                'category' => 'Carreira',
                'author' => 'admin',
                'status' => PostStatus::Published,
                'daysAgo' => 24,
                'content' => <<<MD
                    Antes de marcar uma reunião, escreva **uma página** com:

                    1. o contexto em duas frases;
                    2. a decisão que precisa ser tomada;
                    3. as opções que você enxerga;
                    4. a sua recomendação.

                    Compartilhe com antecedência. Na maioria das vezes, a discussão acontece nos comentários — e a reunião vira uma confirmação de cinco minutos.
                    MD,
            ],
            [
                'title' => 'Migrations com yiisoft/db-migration',
                'excerpt' => 'Versionando o schema do banco junto com o código.',
                'category' => 'Tecnologia',
                'author' => 'editor',
                'status' => PostStatus::Pending,
                'daysAgo' => 1,
                'content' => <<<MD
                    As migrations ficam em `src/Migration` e são classes que implementam `RevertibleMigrationInterface`.

                    ```bash
                    ./yii migrate:create create_tag_table
                    ./yii migrate:up
                    ```

                    Cada migration tem `up()` e `down()`, então dá para voltar atrás com segurança.
                    MD,
            ],
            [
                'title' => 'Cinco atalhos de teclado que mudaram meu dia',
                'excerpt' => 'Pequenas otimizações que somam muito tempo no fim do mês.',
                'category' => 'Carreira',
                'author' => 'editor',
                'status' => PostStatus::Rejected,
                'daysAgo' => 3,
                'note' => 'Gostei do tema! Mas faltam os atalhos em si — hoje o texto só tem a introdução. Liste os cinco com um exemplo de uso de cada.',
                'content' => <<<MD
                    Todo mundo tem aqueles atalhos que, depois de aprendidos, não dá mais para viver sem.

                    Neste post, compartilho os cinco que mais uso no dia a dia.
                    MD,
            ],
            [
                'title' => 'Ideias para a próxima série de posts',
                'excerpt' => '',
                'category' => 'Produto',
                'author' => 'marina',
                'status' => PostStatus::Draft,
                'daysAgo' => 0,
                'content' => <<<MD
                    Rascunho de pauta:

                    - entrevistas com usuários: como começar;
                    - métricas de produto que importam;
                    - o que é um bom critério de aceite.
                    MD,
            ],
        ];
    }
}
