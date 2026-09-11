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

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Psr\Http\Message\ServerRequestInterface;
use Thefish12357\AchievementTree\Achievement;
use Thefish12357\AchievementTree\Api\Serializer\AchievementSerializer;
use Tobscure\JsonApi\Document;

class UpdateAchievementController extends AbstractShowController
{
    public $serializer = AchievementSerializer::class;

    public $include = ['parent'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');

        /** @var Achievement $achievement */
        $achievement = Achievement::findOrFail($id);

        $actor->assertCan('edit', $achievement);

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        // 逐字段显式赋值,不使用任何动态属性名/模板拼接,杜绝批量赋值与注入风险
        if (Arr::has($data, 'name')) {
            $achievement->name = Arr::get($data, 'name');
        }
        if (Arr::has($data, 'description')) {
            $achievement->description = Arr::get($data, 'description');
        }
        if (Arr::has($data, 'icon')) {
            $achievement->icon = Arr::get($data, 'icon');
        }
        if (Arr::has($data, 'imageUrl')) {
            $achievement->image_url = Arr::get($data, 'imageUrl');
        }
        if (Arr::has($data, 'parentId')) {
            $achievement->parent_id = Arr::get($data, 'parentId');
        }
        if (Arr::has($data, 'position')) {
            $achievement->position = (int) Arr::get($data, 'position', 0);
        }
        if (Arr::has($data, 'isHidden')) {
            $achievement->is_hidden = (bool) Arr::get($data, 'isHidden', false);
        }
        if (Arr::has($data, 'points')) {
            $achievement->points = (int) Arr::get($data, 'points', 0);
        }
        if (Arr::has($data, 'series')) {
            $achievement->series = Arr::get($data, 'series');
        }
        if (Arr::has($data, 'seriesSort')) {
            $achievement->series_sort = (int) Arr::get($data, 'seriesSort', 0);
        }
        if (Arr::has($data, 'tier')) {
            $achievement->tier = (int) Arr::get($data, 'tier', 0);
        }
        if (Arr::has($data, 'ruleType')) {
            $achievement->rule_type = Arr::get($data, 'ruleType');
        }
        if (Arr::has($data, 'ruleConfig')) {
            $achievement->rule_config = Arr::get($data, 'ruleConfig');
        }

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
