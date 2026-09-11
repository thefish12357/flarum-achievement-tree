<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thefish12357\AchievementTree\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use Thefish12357\AchievementTree\Achievement;

/**
 * 成就自动解锁通知(通知中心)→ 被解锁的用户。
 */
class AchievementUnlockedBlueprint implements BlueprintInterface
{
    protected Achievement $achievement;
    protected User $user;

    public function __construct(Achievement $achievement, User $user)
    {
        $this->achievement = $achievement;
        $this->user = $user;
    }

    public function getFromUser()
    {
        // 系统自动授予,无具体来源用户
        return null;
    }

    public function getSubject()
    {
        return $this->achievement;
    }

    public function getData()
    {
        return [
            'achievementName' => $this->achievement->name,
            'achievementId' => $this->achievement->id,
        ];
    }

    public static function getType()
    {
        return 'achievementUnlocked';
    }

    public static function getSubjectModel()
    {
        return Achievement::class;
    }
}
