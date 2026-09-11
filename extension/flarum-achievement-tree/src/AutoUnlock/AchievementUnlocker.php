<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thefish12357\AchievementTree\AutoUnlock;

use Flarum\Notification\NotificationSyncer;
use Flarum\User\User;
use Thefish12357\AchievementTree\Achievement;
use Thefish12357\AchievementTree\Notification\AchievementUnlockedBlueprint;
use Thefish12357\AchievementTree\Rule\RuleRegistry;

/**
 * 自动解锁引擎:检查某用户是否满足任一自动解锁规则并授予 + 发通知。
 */
class AchievementUnlocker
{
    protected NotificationSyncer $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * 检查用户是否满足自动解锁规则。
     *
     * 反复扫描直至稳定,以天然支持级联(如 has_achievement 链),
     * 并用迭代上限防止成环导致死循环。
     */
    public function checkUser(User $user): void
    {
        $rules = Achievement::whereNotNull('rule_type')->get();

        if ($rules->isEmpty()) {
            return;
        }

        $maxIterations = $rules->count() + 1;
        $iteration = 0;

        do {
            $granted = false;

            foreach ($rules as $achievement) {
                if ($achievement->users()->where('users.id', $user->id)->exists()) {
                    continue;
                }

                $rule = RuleRegistry::get($achievement->rule_type);

                if (! $rule) {
                    continue;
                }

                $config = $achievement->rule_config;
                if (! is_array($config)) {
                    $config = [];
                }

                if (! $rule->evaluate($user, $config)) {
                    continue;
                }

                if (AchievementGranter::grant($user, $achievement, null)) {
                    $this->notifications->sync(
                        new AchievementUnlockedBlueprint($achievement, $user),
                        [$user]
                    );
                    $granted = true;
                }
            }

            $iteration++;
        } while ($granted && $iteration < $maxIterations);
    }
}
