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
        $achievement->slug = Arr::get($data, 'slug');
        $achievement->description = Arr::get($data, 'description');
        $achievement->icon = Arr::get($data, 'icon');
        $achievement->image_url = Arr::get($data, 'imageUrl');
        $achievement->parent_id = Arr::get($data, 'parentId');
        $achievement->position = (int) Arr::get($data, 'position', 0);
        $achievement->is_hidden = (bool) Arr::get($data, 'isHidden', false);
        $achievement->points = (int) Arr::get($data, 'points', 0);
        $achievement->series = Arr::get($data, 'series');
        $achievement->tier = (int) Arr::get($data, 'tier', 0);
        $achievement->rule_type = Arr::get($data, 'ruleType');

        $series = Arr::get($data, 'series');
        $seriesName = Arr::get($data, 'seriesName');

        if ($series !== null && $series !== '') {
            if ($seriesName !== null && $seriesName !== '') {
                $achievement->series_name = $seriesName;
            } else {
                // 新建时未填系列名,则继承该系列已有的名称
                $achievement->series_name = Achievement::existingSeriesName($series);
            }
        }

        $achievement->save();

        // 若显式修改了系列名称,则同步到同系列其它成就
        if ($series !== null && $series !== '' && $seriesName !== null && $seriesName !== '') {
            Achievement::syncSeriesName($series, $seriesName);
        }

        return $achievement;
    }
}
