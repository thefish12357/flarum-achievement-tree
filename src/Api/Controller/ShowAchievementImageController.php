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

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Thefish12357\AchievementTree\AchievementImage;

/**
 * 按 id 对外提供 storage 内的图片(徽章图/证明图)。
 * 图片不再放 public 直出,统一走此路由,带长缓存与正确 Content-Type。
 */
class ShowAchievementImageController implements RequestHandlerInterface
{
    protected $diskMap = [
        AchievementImage::TYPE_BADGE => 'achievement-tree-badges',
        AchievementImage::TYPE_PROOF => 'achievement-tree-proofs',
    ];

    /** @var FilesystemFactory */
    protected $filesystems;

    public function __construct(FilesystemFactory $filesystems)
    {
        $this->filesystems = $filesystems;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = Arr::get($request->getQueryParams(), 'id');

        /** @var AchievementImage $image */
        $image = AchievementImage::findOrFail($id);

        $diskName = $this->diskMap[$image->type] ?? null;

        if (! $diskName) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        /** @var Filesystem $disk */
        $disk = $this->filesystems->disk($diskName);

        if (! $disk->exists($image->path)) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
        }

        $response = new Response();
        $response = $response
            ->withHeader('Content-Type', $image->mime_type ?: 'application/octet-stream')
            ->withHeader('Cache-Control', 'public, max-age=31536000, immutable');

        $response->getBody()->write($disk->get($image->path));

        return $response;
    }
}
