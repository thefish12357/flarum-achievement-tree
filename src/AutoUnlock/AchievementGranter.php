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

use Carbon\Carbon;
use Flarum\User\User;
use Thefish12357\AchievementTree\Achievement;

/**
 * 统一授予服务:手动授予与自动解锁共用,保证授予逻辑只有一处。
 */
class AchievementGranter
{
    /**
     * 把成就授予用户(幂等,已授予则跳过)。返回是否实际授予。
     */
    public static function grant(User $user, Achievement $achievement, ?int $awardedById): bool
    {
        if ($achievement->users()->where('users.id', $user->id)->exists()) {
            return false;
        }

        $achievement->users()->attach($user->id, [
            'awarded_at' => Carbon::now(),
            'awarded_by_id' => $awardedById,
            'is_displayed' => true,
        ]);

        return true;
    }
}
