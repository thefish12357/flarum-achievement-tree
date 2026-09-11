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

use Flarum\User\Event\LoggedIn;
use Thefish12357\AchievementTree\AutoUnlock\AchievementUnlocker;

/**
 * 用户登录后,检查其是否满足「注册满天数」等与时间相关的自动解锁规则。
 */
class UnlockOnLoggedIn
{
    protected AchievementUnlocker $unlocker;

    public function __construct(AchievementUnlocker $unlocker)
    {
        $this->unlocker = $unlocker;
    }

    public function handle(LoggedIn $event): void
    {
        $this->unlocker->checkUser($event->user);
    }
}
