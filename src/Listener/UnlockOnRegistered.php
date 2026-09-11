<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thefish12357\AchievementTree\Listener;

use Flarum\User\Event\Registered;
use Thefish12357\AchievementTree\AutoUnlock\AchievementUnlocker;

/**
 * 新用户注册(已分配默认角色)后,检查其是否满足自动解锁规则。
 */
class UnlockOnRegistered
{
    protected AchievementUnlocker $unlocker;

    public function __construct(AchievementUnlocker $unlocker)
    {
        $this->unlocker = $unlocker;
    }

    public function handle(Registered $event): void
    {
        $this->unlocker->checkUser($event->user);
    }
}
