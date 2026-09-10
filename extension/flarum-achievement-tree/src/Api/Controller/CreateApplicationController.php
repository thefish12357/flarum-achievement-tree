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
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Thefish12357\AchievementTree\Achievement;
use Thefish12357\AchievementTree\AchievementApplication;
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

        $application = new AchievementApplication();
        $application->user_id = $actor->id;
        $application->achievement_id = $achievement->id;
        $application->message = Arr::get($data, 'message');
        $application->proof_files = $proofFiles;
        $application->status = AchievementApplication::PENDING;
        $application->save();

        return $application;
    }
}
