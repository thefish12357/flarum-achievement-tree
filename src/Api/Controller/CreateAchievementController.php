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
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Psr\Http\Message\ServerRequestInterface;
use Thefish12357\AchievementTree\Achievement;
use Thefish12357\AchievementTree\Api\Serializer\AchievementSerializer;
use Tobscure\JsonApi\Document;

class CreateAchievementController extends AbstractCreateController
{
    public $serializer = AchievementSerializer::class;

    public $include = ['parent'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('create', Achievement::class);

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        $achievement = new Achievement();
        $achievement->name = Arr::get($data, 'name');
        $achievement->description = Arr::get($data, 'description');
        $achievement->icon = Arr::get($data, 'icon');
        $achievement->image_url = Arr::get($data, 'imageUrl');
        $achievement->parent_id = Arr::get($data, 'parentId');
        $achievement->position = (int) Arr::get($data, 'position', 0);
        $achievement->is_hidden = (bool) Arr::get($data, 'isHidden', false);
        $achievement->points = (int) Arr::get($data, 'points', 0);
        $achievement->series = Arr::get($data, 'series');
        $achievement->series_sort = (int) Arr::get($data, 'seriesSort', 0);
        $achievement->tier = (int) Arr::get($data, 'tier', 0);
        $achievement->rule_type = Arr::get($data, 'ruleType');
        $achievement->rule_config = Arr::get($data, 'ruleConfig');

        // 保存自身 + 同步系列排序属于多次写库,用事务保证一致性
        DB::transaction(function () use ($achievement) {
            $achievement->save();

            // 系列排序对整个系列生效:同系列成就统一排序值
            if ($achievement->series) {
                Achievement::syncSeriesSort($achievement->series, (int) $achievement->series_sort);
            }
        });

        return $achievement;
    }
}
