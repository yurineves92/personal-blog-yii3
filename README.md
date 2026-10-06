# Miniblog — Yii3 + MySQL + Nginx

Miniblog com **landing page**, **blog público** e **painel CMS** com três papéis:
**administrador**, **revisor** e **editor**. Roda em Docker (Nginx + PHP-FPM 8.4 + MySQL 8.4).

## Subindo o projeto

```bash
cp .env.example .env      # opcional: os valores padrão já funcionam
docker compose up -d --build
```

Na primeira subida o contêiner PHP instala as dependências do Composer, espera o MySQL,
aplica as migrations e popula o banco com dados de exemplo. Acompanhe com `docker compose logs -f php`.

| O quê | Endereço |
|---|---|
| Landing page | http://localhost:8080 |
| Blog | http://localhost:8080/blog |
| Painel CMS | http://localhost:8080/admin |
| MySQL (host) | `localhost:3307` · usuário `miniblog` / senha `miniblog` |

### Usuários de exemplo

| Papel | E-mail | Senha |
|---|---|---|
| Administrador | admin@miniblog.test | admin123 |
| Revisor | revisor@miniblog.test | revisor123 |
| Editor | editor@miniblog.test | editor123 |
| Editor | marina@miniblog.test | marina123 |

> Troque essas senhas (ou crie usuários novos e desative os de exemplo) antes de expor o sistema.

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

O controle de acesso usa `yiisoft/rbac`: a hierarquia fica em
[`src/Auth/Rbac/RbacItemsStorage.php`](src/Auth/Rbac/RbacItemsStorage.php), o papel de cada usuário
vem da coluna `user.role` ([`UserRoleAssignmentsStorage`](src/Auth/Rbac/UserRoleAssignmentsStorage.php)) e a regra
“próprio post editável” está em [`OwnEditablePostRule`](src/Auth/Rbac/OwnEditablePostRule.php).
As transições de status ficam em [`src/Blog/PostWorkflow.php`](src/Blog/PostWorkflow.php).

## Funcionalidades

- **Landing page**: hero, post em destaque, últimos posts, categorias, seção “sobre”, mais lidos e CTA.
  Os textos são editáveis em *Painel → Configurações*.
- **Blog**: listagem paginada, busca, filtro por categoria, página do post (Markdown), posts relacionados e contador de leituras.
- **Painel**: dashboard por papel, CRUD de posts com editor Markdown, fila de revisão, prévia,
  CRUD de categorias e de usuários (com matriz de permissões), configurações do site e perfil (troca de senha).
- Segurança: CSRF em todos os formulários, senhas com `password_hash`, HTML do Markdown escapado,
  proteção contra open redirect no login, o sistema nunca fica sem um admin ativo.

## Estrutura

```
config/                 configuração Yii3 (rotas em config/common/routes.php)
docker/                 Dockerfile do PHP, entrypoint e config do Nginx
assets/                 CSS/JS do site (main) e do painel (admin)
src/
  Auth/Rbac/            papéis, permissões e regras RBAC
  Blog/                 Post, Category, repositórios e PostWorkflow
  User/                 User (identity), Role e UserRepository
  Site/                 SettingRepository (textos da landing)
  Migration/            migrations do banco
  Console/              comandos app:seed e user:create
  Web/Site/             landing page e blog público
  Web/Admin/            painel CMS (uma pasta por recurso: action + form + template)
  Web/Shared/           layouts, middleware de acesso e helpers
```

## Comandos úteis

```bash
docker compose exec php ./yii migrate:up                     # aplica migrations
docker compose exec php ./yii migrate:create nome_da_tabela  # nova migration
docker compose exec php ./yii app:seed                       # dados de exemplo (só se o banco estiver vazio)
docker compose exec php ./yii user:create email@x.com senha123 admin "Nome"
docker compose exec php composer require vendor/pacote
docker compose down -v                                       # apaga banco e vendor (recomeça do zero)
```

Com `make` disponível: `make up`, `make logs`, `make shell`, `make yii c="migrate:history"`, `make reset`.

> **Nota sobre `vendor/`:** no Docker, `vendor/` fica num volume nomeado (bind mounts no Windows/macOS deixam
> cada request ~10× mais lento). Para ter autocompletar na IDE, rode também `composer install` no host.
