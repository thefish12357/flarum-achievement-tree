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
use Psr\Http\Message\ServerRequestInterface;
use Thefish12357\AchievementTree\Achievement;
use Thefish12357\AchievementTree\Api\Serializer\AchievementSerializer;
use Tobscure\JsonApi\Document;

class UpdateAchievementController extends AbstractShowController
{
    public $serializer = AchievementSerializer::class;

    public $include = ['parent'];

    /**
     * 前端属性名 => 数据表列名
     */
    private const FIELD_MAP = [
        'name' => 'name',
        'description' => 'description',
        'icon' => 'icon',
        'imageUrl' => 'image_url',
        'parentId' => 'parent_id',
        'position' => 'position',
        'isHidden' => 'is_hidden',
        'points' => 'points',
        'series' => 'series',
        'seriesSort' => 'series_sort',
        'tier' => 'tier',
        'ruleType' => 'rule_type',
        'ruleConfig' => 'rule_config',
    ];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');

        /** @var Achievement $achievement */
        $achievement = Achievement::findOrFail($id);

        $actor->assertCan('edit', $achievement);

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        foreach (self::FIELD_MAP as $attribute => $column) {
            if (Arr::has($data, $attribute)) {
                $achievement->{$column} = Arr::get($data, $attribute);
            }
        }

        $achievement->save();

        // 系列排序对整个系列生效:同系列成就统一排序值
        if ($achievement->series) {
            Achievement::syncSeriesSort($achievement->series, (int) $achievement->series_sort);
        }

        return $achievement;
    }
}
