<?php

declare(strict_types=1);

namespace App\Site;

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Textos editáveis do site (landing page, autor, projetos, rodapé), guardados na tabela `setting`.
 */
final class SettingRepository
{
    public const DEFAULTS = [
        'site_name' => 'Yuri Neves',
        'site_tagline' => 'PHP, Yii3 e APIs — anotações de quem constrói software.',
        'hero_title' => 'PHP moderno, Yii3 e APIs que aguentam produção.',
        'hero_subtitle' => 'Tutoriais, decisões de arquitetura e código de verdade. Tudo o que aprendo construindo back-ends, documentado para você (e para o meu eu do futuro).',
        'hero_cta' => 'Ler os artigos',
        'about_title' => 'Sobre mim',
        'about_text' => "Sou desenvolvedor PHP em Curitiba e gosto de construir coisas — principalmente APIs e aplicações web.\n\nNo GitHub você encontra projetos com Laravel, Slim, Lumen e Yii. Este blog é o meu laboratório: foi feito com Yii3, MySQL e Nginx, e cada artigo passa por um fluxo de revisão antes de ser publicado.",
        'author_name' => 'Yuri Neves',
        'author_role' => 'Desenvolvedor PHP · Back-end & APIs',
        'author_location' => 'Curitiba, Brasil',
        'author_avatar' => 'https://github.com/yurineves92.png',
        'github_url' => 'https://github.com/yurineves92',
        'x_url' => 'https://x.com/yurineves92',
        'stack' => 'PHP 8, Yii3, Laravel, Slim, Lumen, MySQL, REST, OpenAPI, Docker, Vue',
        'projects' => "crm-livewire-v3 | https://github.com/yurineves92/crm-livewire-v3 | CRM com Laravel, Livewire e Tailwind CSS: clientes, negócios e times com controle de acesso por papéis. | Laravel\n"
            . "travel-api | https://github.com/yurineves92/travel-api | API REST em Laravel para uma agência de viagens. | API\n"
            . "car-parking-api | https://github.com/yurineves92/car-parking-api | API em Laravel 10 para gestão de estacionamentos, consumida por clientes em Vue 3 e React. | API\n"
            . "url-shortener | https://github.com/yurineves92/url-shortener | Encurtador de URLs com Slim Framework, Twig e MySQL. | Slim\n"
            . "ranking-api | https://github.com/yurineves92/ranking-api | API de ranking construída com Slim Framework 4. | Slim\n"
            . "working-time-app | https://github.com/yurineves92/working-time-app | Controle de ponto pessoal feito com Yii Framework 2. | Yii",
        'footer_text' => 'Feito com Yii3, MySQL e Nginx.',
    ];

    /** @var array<string, string>|null */
    private ?array $values = null;

    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function get(string $name): string
    {
        $this->load();
        return $this->values[$name] ?? self::DEFAULTS[$name] ?? '';
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $this->load();
        return array_merge(self::DEFAULTS, $this->values ?? []);
    }

    /**
     * Itens de uma configuração separada por vírgulas (ex.: `stack`).
     *
     * @return string[]
     */
    public function list(string $name): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->get($name)))));
    }

    /**
     * Projetos em destaque, um por linha: `nome | url | descrição | etiqueta`.
     *
     * @return list<array{name: string, url: string, description: string, tag: string}>
     */
    public function projects(): array
    {
        $projects = [];
        foreach (preg_split('/\R/', $this->get('projects')) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (($parts[0] ?? '') === '') {
                continue;
            }
            $url = $parts[1] ?? '';
            $projects[] = [
                'name' => $parts[0],
                'url' => preg_match('~^https?://~i', $url) ? $url : '',
                'description' => $parts[2] ?? '',
                'tag' => $parts[3] ?? '',
            ];
        }
        return $projects;
    }

    /**
     * @param array<string, string> $values
     */
    public function save(array $values): void
    {
        $this->db->transaction(function () use ($values): void {
            foreach ($values as $name => $value) {
                if (!array_key_exists($name, self::DEFAULTS)) {
                    continue;
                }
                $this->db->createCommand()
                    ->upsert('setting', ['name' => $name, 'value' => $value], ['value' => $value])
                    ->execute();
            }
        });
        $this->values = null;
    }

    private function load(): void
    {
        if ($this->values !== null) {
            return;
        }

        $this->values = [];
        try {
            foreach ($this->db->select(['name', 'value'])->from('setting')->all() as $row) {
                $this->values[(string) $row['name']] = (string) $row['value'];
            }
        } catch (\Throwable) {
            // Tabela ainda não criada (antes das migrations): usa os valores padrão.
        }
    }
}
