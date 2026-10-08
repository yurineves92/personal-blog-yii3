# Blog pessoal com Yii3 + MySQL + Nginx

Blog pessoal de **Yuri Neves** com landing page, blog em Markdown (com diagramas Mermaid),
**painel CMS** com três papéis (administrador, revisor e editor), **API REST** documentada com
Swagger e um app **PWA em Vue** para leitura offline. Roda em Docker (Nginx + PHP-FPM 8.4 + MySQL 8.4).

## Subindo o projeto

```bash
cp .env.example .env      # opcional: os valores padrão já funcionam
docker compose up -d --build
```

Na primeira subida o contêiner PHP instala as dependências do Composer, espera o MySQL,
aplica as migrations e popula o banco. Acompanhe com `docker compose logs -f php`.

| O quê | Endereço |
|---|---|
| Landing page | http://localhost:8080 |
| Blog | http://localhost:8080/blog |
| Painel CMS | http://localhost:8080/admin |
| API (Swagger UI, no painel) | http://localhost:8080/admin/api |
| MySQL (host) | `localhost:3307` · usuário `miniblog` / senha `miniblog` |

### Acesso inicial

| Papel | E-mail | Senha |
|---|---|---|
| Administrador (Yuri Neves) | admin@miniblog.test | admin123 |

> Troque a senha em **Painel → Meu perfil** antes de expor o sistema. Revisores e editores podem ser
> criados em **Painel → Usuários** (ou com `./yii user:create`).

## Conteúdo inicial (seed)

Os artigos iniciais ficam versionados em [`seed/posts/`](seed/posts), um arquivo Markdown por artigo:

```markdown
---
title: Título do artigo
excerpt: Resumo exibido nos cards
category: Yii3
cover: /covers/titulo-do-artigo.webp
days_ago: 10
---
Conteúdo em **Markdown**, com blocos ```mermaid para diagramas.
```

Capas: as dos artigos iniciais ficam em `public/covers/` (versionadas). Capas novas são enviadas pelo editor do painel
(JPG, PNG ou WebP até 5 MB, tipo verificado pelo conteúdo) e salvas em `public/uploads/covers/` (fora do Git).

- **Série Yii3** (5 artigos): pacotes, caminho de uma requisição, configuração e DI, rotas e middlewares, dados/formulários/views.
- **Série Bastidores** (5 artigos): como este blog foi construído — Docker, banco, RBAC, telas, API e PWA.

```bash
docker compose exec php ./yii app:seed            # popula se o banco estiver vazio
docker compose exec php ./yii app:seed --fresh    # APAGA tudo e recria a partir de seed/posts
```

## Papéis e fluxo editorial

```
rascunho ──enviar──▶ em revisão ──aprovar──▶ publicado
   ▲                     │                       │
   └──── rejeitado ◀─────┘ rejeitar (comentário)  │
   ▲                                             │
   └──────────────── despublicar ◀───────────────┘
```

| Permissão | Editor | Revisor | Admin |
|---|:-:|:-:|:-:|
| Acessar o painel, criar posts | ✓ | ✓ | ✓ |
| Editar/excluir os **próprios** posts em rascunho/rejeitado | ✓ | ✓¹ | ✓ |
| Ver posts de todos os autores | | ✓ | ✓ |
| Editar qualquer post | | ✓ | ✓ |
| Aprovar, rejeitar, despublicar | | ✓² | ✓ |
| Publicar direto (pular revisão) | | | ✓ |
| Excluir qualquer post | | | ✓ |
| Categorias, usuários, configurações do site | | | ✓ |

¹ O revisor exclui apenas os próprios posts. ² O revisor não aprova os próprios posts.

RBAC com `yiisoft/rbac`: hierarquia em [`RbacItemsStorage`](src/Auth/Rbac/RbacItemsStorage.php), papel do usuário
lido de `user.role` ([`UserRoleAssignmentsStorage`](src/Auth/Rbac/UserRoleAssignmentsStorage.php)), regra de autoria em
[`OwnEditablePostRule`](src/Auth/Rbac/OwnEditablePostRule.php) e transições em [`PostWorkflow`](src/Blog/PostWorkflow.php).

## API REST

Base: `/admin/api/v1` · documentação interativa em **Painel → API** (Swagger UI) · spec em `/admin/api/openapi.json`
(gerada com `swagger-php` a partir dos atributos em [`src/Web/Api/V1`](src/Web/Api/V1)).

**Pública (sem token, só conteúdo publicado, CORS liberado):**

| Método | Caminho | Descrição |
|---|---|---|
| GET | `/public/site` | dados do site e do autor |
| GET | `/public/posts?category=&q=&page=&per_page=` | posts publicados |
| GET | `/public/posts/{slug}` | post com `content_html` renderizado |
| GET | `/public/categories` | categorias com posts |

**Autenticada (`Authorization: Bearer <token>`, mesmas permissões do painel):**

| Método | Caminho | Descrição |
|---|---|---|
| POST | `/auth/token` | e-mail + senha → token (30 dias) |
| GET | `/me` | usuário autenticado |
| GET / POST | `/posts` | listar / criar |
| GET / PUT / PATCH / DELETE | `/posts/{id}` | detalhar / atualizar / excluir |
| POST | `/posts/{id}/{submit\|approve\|reject\|unpublish}` | fluxo editorial |
| GET / POST | `/categories` | listar / criar (admin) |
| PUT / PATCH / DELETE | `/categories/{id}` | atualizar / excluir (admin) |

```bash
curl -X POST http://localhost:8080/admin/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@miniblog.test","password":"admin123"}'

curl http://localhost:8080/admin/api/v1/posts -H "Authorization: Bearer mb_..."
```

Erros seguem a RFC 9457 (`application/problem+json`). Tokens são guardados como hash SHA-256 e podem ser
gerados/revogados na página **API** do painel.

## App PWA (leitura offline)

O cliente em Vue 3 fica num repositório/pasta separado, **`blog-yii3-pwa`** (ao lado deste projeto). Ele consome os
endpoints públicos acima e guarda os artigos lidos para leitura offline. Veja o README dele para rodar.

## Estrutura

```
config/                 configuração Yii3 (rotas em config/common/routes.php)
docker/                 Dockerfile do PHP, entrypoint e config do Nginx
assets/                 CSS/JS do site (main) e do painel (admin)
seed/posts/             artigos iniciais em Markdown
src/
  Api/                  tokens da API
  Auth/Rbac/            papéis, permissões e regras RBAC
  Blog/                 Post, Category, repositórios, PostService e PostWorkflow
  User/                 User (identity), Role e UserRepository
  Site/                 SettingRepository (textos da landing, autor, projetos)
  Migration/            migrations do banco
  Console/              comandos app:seed e user:create
  Web/Site/             landing page e blog público
  Web/Admin/            painel CMS (uma pasta por recurso: action + form + template)
  Web/Api/              API REST (controllers, schemas OpenAPI, middlewares)
  Web/Shared/           layouts, middlewares, helpers e renderização Mermaid
```

## Comandos úteis

```bash
docker compose exec php ./yii migrate:up                     # aplica migrations
docker compose exec php ./yii migrate:create nome_da_tabela  # nova migration
docker compose exec php ./yii app:seed --fresh               # recria o conteúdo inicial
docker compose exec php ./yii user:create email@x.com senha123 editor "Nome"
docker compose exec php composer require vendor/pacote
docker compose down -v                                       # apaga banco e vendor (recomeça do zero)
```

Com `make` disponível: `make up`, `make logs`, `make shell`, `make yii c="migrate:history"`, `make reset`.

> **Nota sobre `vendor/`:** no Docker, `vendor/` fica num volume nomeado (bind mounts no Windows/macOS deixam
> cada request ~10× mais lento). Para ter autocompletar na IDE, rode também `composer install` no host.
