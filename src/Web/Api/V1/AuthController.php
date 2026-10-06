<?php

declare(strict_types=1);

namespace App\Web\Api\V1;

use App\Api\ApiTokenRepository;
use App\User\User;
use App\User\UserRepository;
use App\Web\Api\ApiResponder;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Status;
use Yiisoft\Security\PasswordHasher;
use Yiisoft\User\CurrentUser;

final readonly class AuthController
{
    public function __construct(
        private ApiResponder $responder,
        private UserRepository $users,
        private ApiTokenRepository $tokens,
        private PasswordHasher $passwordHasher,
        private CurrentUser $currentUser,
    ) {}

    #[OA\Post(
        path: '/auth/token',
        operationId: 'createToken',
        description: 'Troca e-mail e senha por um token Bearer válido por 30 dias. O token é exibido apenas nesta resposta.',
        summary: 'Gerar token de acesso',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@miniblog.test'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'admin123'),
                    new OA\Property(property: 'name', description: 'Identificação do token (ex.: nome do app)', type: 'string', example: 'cli'),
                ],
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Token criado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'mb_3f1c…'),
                    new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function token(ServerRequestInterface $request): ResponseInterface
    {
        $body = ApiResponder::body($request);
        if ($body === null) {
            return $this->responder->problem(Status::BAD_REQUEST, 'JSON inválido.');
        }

        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $errors = [];
        if ($email === '') {
            $errors['email'] = ['Informe o e-mail.'];
        }
        if ($password === '') {
            $errors['password'] = ['Informe a senha.'];
        }
        if ($errors !== []) {
            return $this->responder->validationFailed($errors);
        }

        $user = $this->users->findByEmail($email);
        if ($user === null || !$user->isActive || !$this->passwordHasher->validate($password, $user->passwordHash)) {
            return $this->responder->unauthorized('E-mail ou senha incorretos.');
        }

        $created = $this->tokens->create($user->id, (string) ($body['name'] ?? 'api'));

        return $this->responder->created([
            'token' => $created['token'],
            'token_type' => 'Bearer',
            'expires_at' => Resource::date($created['model']->expiresAt),
            'user' => Resource::user($user),
        ]);
    }

    #[OA\Get(
        path: '/me',
        operationId: 'me',
        summary: 'Usuário autenticado',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
        ],
    )]
    public function me(): ResponseInterface
    {
        /** @var User $user */
        $user = $this->currentUser->getIdentity();
        return $this->responder->json(Resource::user($user));
    }

    /**
     * Qualquer rota inexistente dentro de /admin/api/v1 responde 404 em JSON (e não com a página HTML).
     */
    public function notFound(): ResponseInterface
    {
        return $this->responder->notFound('Endpoint não encontrado. Veja a documentação em /admin/api.');
    }
}
