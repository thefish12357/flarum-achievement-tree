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

use Flarum\Post\Event\Posted;
use Thefish12357\AchievementTree\AutoUnlock\AchievementUnlocker;

/**
 * 用户发帖后,检查其是否满足「发帖数」等自动解锁规则。
 */
class UnlockOnPosted
{
    protected AchievementUnlocker $unlocker;

    public function __construct(AchievementUnlocker $unlocker)
    {
        $this->unlocker = $unlocker;
    }

    public function handle(Posted $event): void
    {
        $user = $event->post->user;
        if ($user) {
            $this->unlocker->checkUser($user);
        }
    }
}
