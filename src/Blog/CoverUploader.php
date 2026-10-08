<?php

declare(strict_types=1);

namespace App\Blog;

use DomainException;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Salva imagens de capa enviadas pelo painel em public/uploads/covers.
 *
 * O tipo é verificado pelo conteúdo do arquivo (não pela extensão nem pelo
 * Content-Type enviado pelo navegador) e o nome final é aleatório.
 */
final readonly class CoverUploader
{
    public const PUBLIC_DIR = '/uploads/covers';
    public const MAX_BYTES = 5 * 1024 * 1024;

    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private Aliases $aliases,
    ) {}

    /**
     * @return string Caminho público da imagem (ex.: /uploads/covers/cover-3f9a1c2b7d4e.webp)
     * @throws DomainException Quando o arquivo é inválido.
     */
    public function store(UploadedFileInterface $file): string
    {
        if ($file->getError() === UPLOAD_ERR_INI_SIZE || $file->getError() === UPLOAD_ERR_FORM_SIZE) {
            throw new DomainException('A imagem deve ter no máximo 5 MB.');
        }
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new DomainException('Não foi possível receber a imagem. Tente novamente.');
        }
        if (($file->getSize() ?? 0) > self::MAX_BYTES) {
            throw new DomainException('A imagem deve ter no máximo 5 MB.');
        }

        $stream = $file->getStream();
        $stream->rewind();
        $contents = $stream->getContents();

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        $extension = self::EXTENSIONS[$mime] ?? null;
        if ($extension === null || @getimagesizefromstring($contents) === false) {
            throw new DomainException('Envie uma imagem JPG, PNG ou WebP.');
        }

        $dir = $this->aliases->get('@public' . self::PUBLIC_DIR);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new DomainException('Pasta de uploads indisponível no servidor.');
        }

        $name = 'cover-' . bin2hex(random_bytes(6)) . '.' . $extension;
        if (file_put_contents($dir . '/' . $name, $contents) === false) {
            throw new DomainException('Não foi possível salvar a imagem no servidor.');
        }

        return self::PUBLIC_DIR . '/' . $name;
    }

    /**
     * Apaga uma capa enviada anteriormente. Ignora URLs externas e capas da pasta /covers (versionadas).
     */
    public function delete(?string $publicPath): void
    {
        if ($publicPath === null || !str_starts_with($publicPath, self::PUBLIC_DIR . '/')) {
            return;
        }
        $file = $this->aliases->get('@public') . $publicPath;
        if (basename($file) === basename($publicPath) && is_file($file)) {
            @unlink($file);
        }
    }
}
