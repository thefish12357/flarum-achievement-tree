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

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Thefish12357\AchievementTree\Achievement;
use Thefish12357\AchievementTree\Api\Serializer\AchievementSerializer;
use Tobscure\JsonApi\Document;

class ListAchievementsController extends AbstractListController
{
    public $serializer = AchievementSerializer::class;

    public $include = ['parent'];

    public $optionalInclude = ['children'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $filters = $this->extractFilter($request);
        $include = $this->extractInclude($request);

        $userId = Arr::get($filters, 'userId');

        $query = Achievement::query()
            ->with($include)
            ->orderBy('position')
            ->orderBy('id');

        // 隐藏成就:仅管理员、或查询本人成就时可见
        $isSelf = $userId !== null && (string) $actor->id === (string) $userId;
        if (! $actor->isAdmin() && ! $isSelf) {
            $query->where('is_hidden', 0);
        }

        if ($userId !== null) {
            $query->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            });
        }

        return $query->get();
    }
}
