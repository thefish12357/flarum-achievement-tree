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
 * 接收用户提交的"申请证明材料"图片上传,保存到 public/assets/achievement-proofs 并返回可访问 URL。
 * 仅登录用户可调用(任意登录用户都能为自己的申请上传证明)。
 */
class UploadApplicationProofImageController implements RequestHandlerInterface
{
    /** @var Filesystem */
    protected $disk;

    public function __construct(FilesystemFactory $filesystem)
    {
        $this->disk = $filesystem->disk('achievement-tree-proofs');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $files = $request->getUploadedFiles();

        if (empty($files['file'])) {
            return new JsonResponse(['errors' => [['detail' => '未接收到文件']]], 400);
        }

        $file = $files['file'];

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return new JsonResponse(['errors' => [['detail' => '上传失败,错误码 '.$file->getError()]]], 400);
        }

        // 大小限制(2MB),防止资源耗尽
        $maxSize = 2 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            return new JsonResponse(['errors' => [['detail' => '图片过大,最大 2MB']]], 400);
        }

        $clientName = $file->getClientFilename();
        $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        // 仅允许位图,禁用 SVG(SVG 可内嵌脚本造成存储型 XSS)
        $allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        $allowedMime = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
        $mime = $file->getClientMediaType();

        if (! in_array($ext, $allowedExt, true) || ! in_array($mime, $allowedMime, true)) {
            return new JsonResponse(['errors' => [['detail' => '不支持的图片类型,仅允许: '.implode('/', $allowedExt)]]], 400);
        }

        $contents = $file->getStream()->getContents();

        // 校验真实图片内容,防止扩展名/MIME 伪装
        if (@getimagesizefromstring($contents) === false) {
            return new JsonResponse(['errors' => [['detail' => '文件内容不是有效的图片']]], 400);
        }

        $name = Str::random(16).'.'.$ext;
        $this->disk->put($name, $contents);

        return new JsonResponse(['url' => '/assets/achievement-proofs/'.$name], 201);
    }
}
