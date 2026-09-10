<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thefish12357\AchievementTree\Api\Controller;

use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 接收图片上传,保存到 public/assets/badges 并返回可访问 URL。
 * 仅管理员可调用。
 */
class UploadAchievementImageController implements RequestHandlerInterface
{
    /** @var Filesystem */
    protected $disk;

    public function __construct(FilesystemFactory $filesystem)
    {
        $this->disk = $filesystem->disk('achievement-tree-badges');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $files = $request->getUploadedFiles();

        if (empty($files['file'])) {
            return new JsonResponse(['errors' => [['detail' => '未接收到文件']]], 400);
        }

        $file = $files['file'];

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return new JsonResponse(['errors' => [['detail' => '上传失败,错误码 '.$file->getError()]]], 400);
        }

        $clientName = $file->getClientFilename();
        $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];

        if (! in_array($ext, $allowed, true)) {
            return new JsonResponse(['errors' => [['detail' => '不支持的图片类型,仅允许: '.implode('/', $allowed)]]], 400);
        }

        $name = Str::random(16).'.'.$ext;

        $stream = $file->getStream();
        $stream->rewind();
        $this->disk->put($name, $stream->getContents());

        return new JsonResponse(['url' => '/assets/badges/'.$name], 201);
    }
}
