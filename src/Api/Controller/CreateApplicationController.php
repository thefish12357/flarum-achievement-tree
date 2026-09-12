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

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Foundation\ValidationException;
use Flarum\Group\Group;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Thefish12357\AchievementTree\Achievement;
use Thefish12357\AchievementTree\AchievementApplication;
use Thefish12357\AchievementTree\AchievementImage;
use Thefish12357\AchievementTree\Api\Serializer\AchievementApplicationSerializer;
use Tobscure\JsonApi\Document;

class CreateApplicationController extends AbstractCreateController
{
    public $serializer = AchievementApplicationSerializer::class;

    public $include = ['achievement'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('create', AchievementApplication::class);

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        /** @var Achievement $achievement */
        $achievement = Achievement::findOrFail(Arr::get($data, 'achievementId'));

        if ($achievement->users()->where('users.id', $actor->id)->exists()) {
            throw new ValidationException(['achievementId' => '你已经获得该成就']);
        }

        $hasPending = AchievementApplication::query()
            ->where('user_id', $actor->id)
            ->where('achievement_id', $achievement->id)
            ->where('status', AchievementApplication::PENDING)
            ->exists();

        if ($hasPending) {
            throw new ValidationException(['achievementId' => '你已提交过申请,请等待审核']);
        }

        $proofFiles = Arr::get($data, 'proofFiles', []);
        if (! is_array($proofFiles)) {
            throw new ValidationException(['proofFiles' => '证明材料格式不正确']);
        }
        foreach ($proofFiles as $url) {
            if (! is_string($url) || ! preg_match('#^https://#i', $url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                throw new ValidationException(['proofFiles' => '证明材料链接必须是 https 开头的有效 URL']);
            }
        }

        // 申请证明材料图片:仅允许本站上传的证明图(新路径为图片服务路由,旧数据保留 assets 路径)
        $proofImages = Arr::get($data, 'proofImages', []);
        if (! is_array($proofImages)) {
            throw new ValidationException(['proofImages' => '证明材料图片格式不正确']);
        }
        $proofImages = array_values(array_filter($proofImages, function ($url) {
            return is_string($url) && $url !== '';
        }));
        foreach ($proofImages as $url) {
            $isServeUrl = preg_match('#^/achievement-images/(\d+)$#', $url, $m) === 1;
            $isLegacyUrl = preg_match('#^/assets/achievement-proofs/#', $url) === 1;

            if (! $isServeUrl && ! $isLegacyUrl) {
                throw new ValidationException(['proofImages' => '证明材料图片必须是本站上传的图片']);
            }

            if ($isServeUrl) {
                // 服务路由的图片必须真实存在、类型为证明图,且为本人在本申请中上传的
                $exists = AchievementImage::query()
                    ->where('id', (int) $m[1])
                    ->where('type', AchievementImage::TYPE_PROOF)
                    ->where(function ($q) use ($actor) {
                        $q->where('user_id', $actor->id)->orWhereHas('user.groups', function ($gq) {
                            $gq->where('groups.id', Group::ADMINISTRATOR_ID);
                        });
                    })
                    ->exists();

                if (! $exists) {
                    throw new ValidationException(['proofImages' => '证明材料图片无效或无权使用']);
                }
            }
        }

        $message = Arr::get($data, 'message');

        // 申请描述支持文字和图片:文字与图片至少填一项
        if (empty($message) && empty($proofImages)) {
            throw new ValidationException(['message' => '请填写说明或上传证明材料图片(至少一项)']);
        }

        $application = new AchievementApplication();
        $application->user_id = $actor->id;
        $application->achievement_id = $achievement->id;
        $application->message = $message;
        $application->proof_files = $proofFiles;
        $application->proof_images = $proofImages;
        $application->status = AchievementApplication::PENDING;
        $application->save();

        return $application;
    }
}
