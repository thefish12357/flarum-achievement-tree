<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Api\Serializer\UserSerializer;
use Flarum\Extend;
use Flarum\Foundation\Paths;
use Flarum\Http\UrlGenerator;
use Flarum\User\User;
use Thefish12357\AchievementTree\Access\AchievementPolicy;
use Thefish12357\AchievementTree\Access\ApplicationPolicy;
use Thefish12357\AchievementTree\Achievement;
use Thefish12357\AchievementTree\AchievementApplication;
use Thefish12357\AchievementTree\Api\Controller;
use Thefish12357\AchievementTree\Api\Serializer\AchievementApplicationSerializer;
use Thefish12357\AchievementTree\Notification\ApplicationReviewedBlueprint;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    // 成就徽章图片上传:声明本扩展专用磁盘(核心并不保证存在 'flarum' 磁盘)
    (new Extend\Filesystem())
        ->disk('achievement-tree-badges', function (Paths $paths, UrlGenerator $url) {
            return [
                'driver' => 'local',
                'root' => $paths->public.'/assets/badges',
                'url' => $url->to('forum')->path('assets/badges'),
            ];
        }),

    (new Extend\Routes('api'))
        ->get('/achievements', 'achievements.index', Controller\ListAchievementsController::class)
        ->post('/achievements', 'achievements.create', Controller\CreateAchievementController::class)
        ->get('/achievements/{id}', 'achievements.show', Controller\ShowAchievementController::class)
        ->patch('/achievements/{id}', 'achievements.update', Controller\UpdateAchievementController::class)
        ->delete('/achievements/{id}', 'achievements.delete', Controller\DeleteAchievementController::class)
        ->post('/achievements/{id}/award', 'achievements.award', Controller\AwardAchievementController::class)
        ->delete('/achievements/{id}/award', 'achievements.revoke', Controller\RevokeAchievementController::class)
        ->post('/achievement-images', 'achievements.upload-image', Controller\UploadAchievementImageController::class)
        ->post('/achievements/{id}/display', 'achievements.display', Controller\ToggleAchievementDisplayController::class)
        ->get('/achievement-applications', 'achievement-applications.index', Controller\ListApplicationsController::class)
        ->post('/achievement-applications', 'achievement-applications.create', Controller\CreateApplicationController::class)
        ->patch('/achievement-applications/{id}', 'achievement-applications.review', Controller\ReviewApplicationController::class),

    // 用户与成就多对多
    (new Extend\Model(User::class))
        ->belongsToMany('achievements', Achievement::class, 'achievement_user'),

    (new Extend\ApiSerializer(UserSerializer::class))
        ->attribute('achievements', function ($serializer, User $user, array $attributes) {
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

            return $achievements->map(function ($achievement) use ($awardedMap) {
                $info = $awardedMap[$achievement->id] ?? null;
                $awardedAt = ($info && $info['at'])
                    ? \Carbon\Carbon::parse($info['at'])->toIso8601String()
                    : null;
                $isDisplayed = $info ? (bool) $info['displayed'] : true;

                return [
                    'id' => (string) $achievement->id,
                    'type' => 'achievements',
                    'attributes' => [
                        'name' => $achievement->name,
                        'slug' => $achievement->slug,
                        'description' => $achievement->description,
                        'icon' => $achievement->icon,
                        'imageUrl' => $achievement->image_url,
                        'parentId' => $achievement->parent_id,
                        'series' => $achievement->series,
                        'seriesName' => $achievement->series_name,
                        'tier' => (int) $achievement->tier,
                        'position' => $achievement->position,
                        'isHidden' => (bool) $achievement->is_hidden,
                        'awardedAt' => $awardedAt,
                        'isDisplayed' => $isDisplayed,
                    ],
                ];
            })->values()->all();
        }),

    (new Extend\Policy())
        ->modelPolicy(Achievement::class, AchievementPolicy::class)
        ->modelPolicy(AchievementApplication::class, ApplicationPolicy::class),

    // 申请审核结果通知(通过/驳回)→ 申请人通知中心
    (new Extend\Notification())
        ->type(ApplicationReviewedBlueprint::class, AchievementApplicationSerializer::class, ['alert']),
];
