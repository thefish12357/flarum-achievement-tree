<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thefish12357\AchievementTree\Api\Serializer;

use Carbon\Carbon;
use Flarum\User\User;
use Thefish12357\AchievementTree\Achievement;
use Thefish12357\AchievementTree\AchievementApplication;

/**
 * 给 UserSerializer 追加 achievements 属性(用户成就树数据)。
 * 从 extend.php 抽离,便于维护与测试。
 */
class UserAchievementsAttribute
{
    /**
     * @param mixed $serializer
     */
    public function __invoke($serializer, User $user, array $attributes): array
    {
        $achievements = $user->achievements;

        // 一次性取出该用户在各成就上的 awarded_at(走 Eloquent 关系,不依赖 DB Facade)
        $awardedMap = [];
        if ($achievements->isNotEmpty()) {
            $rows = Achievement::query()
                ->whereIn('id', $achievements->pluck('id')->all())
                ->with(['users' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }])
                ->get();

            foreach ($rows as $row) {
                $pivot = $row->users->first();
                $awardedMap[$row->id] = ($pivot && $pivot->pivot)
                    ? [
                        'at' => $pivot->pivot->awarded_at,
                        'displayed' => isset($pivot->pivot->is_displayed) ? (bool) $pivot->pivot->is_displayed : true,
                    ]
                    : null;
            }
        }

        // 该用户各成就的"申请证明材料图片",一并内嵌到成就数据,供前端"获得证明"展示
        $proofMap = [];
        $proofRows = AchievementApplication::query()
            ->where('user_id', $user->id)
            ->whereNotNull('proof_images')
            ->get();
        foreach ($proofRows as $proofRow) {
            $p = $proofRow->proof_images;
            if (is_array($p) && count($p)) {
                $proofMap[$proofRow->achievement_id] = $p;
            }
        }

        return $achievements->map(function ($achievement) use ($awardedMap, $proofMap) {
            $info = $awardedMap[$achievement->id] ?? null;
            $awardedAt = ($info && $info['at'])
                ? Carbon::parse($info['at'])->toIso8601String()
                : null;
            $isDisplayed = $info ? (bool) $info['displayed'] : true;

            return [
                'id' => (string) $achievement->id,
                'type' => 'achievements',
                'attributes' => [
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'imageUrl' => $achievement->image_url,
                    'parentId' => $achievement->parent_id,
                    'series' => $achievement->series,
                    'seriesSort' => (int) ($achievement->series_sort ?? 0),
                    'tier' => (int) $achievement->tier,
                    'position' => $achievement->position,
                    'isHidden' => (bool) $achievement->is_hidden,
                    'awardedAt' => $awardedAt,
                    'isDisplayed' => $isDisplayed,
                    'proofImages' => $proofMap[$achievement->id] ?? [],
                ],
            ];
        })->values()->all();
    }
}
