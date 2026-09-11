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
use Flarum\Discussion\Event\Started;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\PostWasLiked;
use Flarum\User\Event\GroupsChanged;
use Flarum\User\Event\LoggedIn;
use Flarum\User\Event\Registered;
use Flarum\User\User;
use Thefish12357\AchievementTree\Access\AchievementPolicy;
use Thefish12357\AchievementTree\Access\ApplicationPolicy;
use Thefish12357\AchievementTree\Achievement;
use Thefish12357\AchievementTree\AchievementApplication;
use Thefish12357\AchievementTree\Api\Controller;
use Thefish12357\AchievementTree\Api\Controller\ListRuleTypesController;
use Thefish12357\AchievementTree\Api\Serializer\AchievementApplicationSerializer;
use Thefish12357\AchievementTree\Api\Serializer\AchievementSerializer;
use Thefish12357\AchievementTree\Listener\UnlockOnDiscussionStarted;
use Thefish12357\AchievementTree\Listener\UnlockOnGroupsChanged;
use Thefish12357\AchievementTree\Listener\UnlockOnLoggedIn;
use Thefish12357\AchievementTree\Listener\UnlockOnPostLiked;
use Thefish12357\AchievementTree\Listener\UnlockOnPosted;
use Thefish12357\AchievementTree\Listener\UnlockOnRegistered;
use Thefish12357\AchievementTree\Notification\ApplicationReviewedBlueprint;
use Thefish12357\AchievementTree\Notification\AchievementUnlockedBlueprint;

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
        })
        ->disk('achievement-tree-proofs', function (Paths $paths, UrlGenerator $url) {
            return [
                'driver' => 'local',
                'root' => $paths->public.'/assets/achievement-proofs',
                'url' => $url->to('forum')->path('assets/achievement-proofs'),
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
        ->post('/achievement-proof-images', 'achievement-applications.upload-proof', Controller\UploadApplicationProofImageController::class)
        ->patch('/achievement-applications/{id}', 'achievement-applications.review', Controller\ReviewApplicationController::class)
        ->get('/achievement-rule-types', 'achievement-rule-types.index', ListRuleTypesController::class),

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
                    ? \Carbon\Carbon::parse($info['at'])->toIso8601String()
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
        }),

    (new Extend\Policy())
        ->modelPolicy(Achievement::class, AchievementPolicy::class)
        ->modelPolicy(AchievementApplication::class, ApplicationPolicy::class),

    // 自动解锁:监听角色变更/注册/发帖/发起主题/被点赞/登录,自动授予满足规则的成就
    (new Extend\Event())
        ->listen(GroupsChanged::class, UnlockOnGroupsChanged::class)
        ->listen(Registered::class, UnlockOnRegistered::class)
        ->listen(Posted::class, UnlockOnPosted::class)
        ->listen(Started::class, UnlockOnDiscussionStarted::class)
        ->listen(PostWasLiked::class, UnlockOnPostLiked::class)
        ->listen(LoggedIn::class, UnlockOnLoggedIn::class),

    // 申请审核结果通知(通过/驳回)→ 申请人通知中心
    (new Extend\Notification())
        ->type(ApplicationReviewedBlueprint::class, AchievementApplicationSerializer::class, ['alert'])
        ->type(AchievementUnlockedBlueprint::class, AchievementSerializer::class, ['alert']),
];
