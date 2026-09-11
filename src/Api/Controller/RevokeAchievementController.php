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

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Thefish12357\AchievementTree\Achievement;

class RevokeAchievementController extends AbstractDeleteController
{
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');
        $userId = Arr::get($request->getQueryParams(), 'userId');

        /** @var Achievement $achievement */
        $achievement = Achievement::findOrFail($id);

        $actor->assertCan('revoke', $achievement);

        $achievement->users()->detach($userId);
    }
}
