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

use Flarum\Discussion\Event\Started;
use Thefish12357\AchievementTree\AutoUnlock\AchievementUnlocker;

/**
 * 用户发起主题帖后,检查其是否满足「主题帖数」等自动解锁规则。
 */
class UnlockOnDiscussionStarted
{
    protected AchievementUnlocker $unlocker;

    public function __construct(AchievementUnlocker $unlocker)
    {
        $this->unlocker = $unlocker;
    }

    public function handle(Started $event): void
    {
        $user = $event->discussion->user;
        if ($user) {
            $this->unlocker->checkUser($user);
        }
    }
}
