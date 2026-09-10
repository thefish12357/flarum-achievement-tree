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

use Carbon\Carbon;
use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Thefish12357\AchievementTree\Achievement;
use Thefish12357\AchievementTree\Api\Serializer\AchievementSerializer;
use Tobscure\JsonApi\Document;

class AwardAchievementController extends AbstractCreateController
{
    public $serializer = AchievementSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');

        /** @var Achievement $achievement */
        $achievement = Achievement::findOrFail($id);

        $actor->assertCan('award', $achievement);

        $body = Arr::get($request->getParsedBody(), 'data', []);
        $userId = Arr::get($body, 'attributes.userId') ?? Arr::get($body, 'userId');

        /** @var User $user */
        $user = User::findOrFail($userId);

        if (! $achievement->users()->where('users.id', $user->id)->exists()) {
            $achievement->users()->attach($user->id, [
                'awarded_at' => Carbon::now(),
                'awarded_by_id' => $actor->id,
                'is_displayed' => true,
            ]);
        }

        return $achievement;
    }
}
