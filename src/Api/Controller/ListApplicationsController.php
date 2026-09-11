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
use Thefish12357\AchievementTree\AchievementApplication;
use Thefish12357\AchievementTree\Api\Serializer\AchievementApplicationSerializer;
use Tobscure\JsonApi\Document;

class ListApplicationsController extends AbstractListController
{
    public $serializer = AchievementApplicationSerializer::class;

    public $include = ['user', 'achievement'];

    public $optionalInclude = ['reviewer'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $filters = $this->extractFilter($request);

        $query = AchievementApplication::query()
            ->with($this->extractInclude($request))
            ->latest();

        if ($actor->isAdmin()) {
            if ($status = Arr::get($filters, 'status')) {
                $query->where('status', $status);
            }

            if ($userId = Arr::get($filters, 'userId')) {
                $query->where('user_id', $userId);
            }
        } else {
            // 普通用户只能看自己提交的申请
            $query->where('user_id', $actor->id);
        }

        return $query->get();
    }
}
