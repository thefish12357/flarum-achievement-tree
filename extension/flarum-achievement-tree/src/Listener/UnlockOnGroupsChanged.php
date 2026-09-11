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

use Flarum\User\Event\GroupsChanged;
use Thefish12357\AchievementTree\AutoUnlock\AchievementUnlocker;

/**
 * 用户角色(group)变更后,检查其是否满足「角色」类自动解锁规则。
 *
 * GroupsChanged 在 User 的 afterSave 中 groups()->sync 执行之后才派发,
 * 此时 $event->user 的群组已是最新,可直接用于判断。
 */
class UnlockOnGroupsChanged
{
    protected AchievementUnlocker $unlocker;

    public function __construct(AchievementUnlocker $unlocker)
    {
        $this->unlocker = $unlocker;
    }

    public function handle(GroupsChanged $event): void
    {
        $this->unlocker->checkUser($event->user);
    }
}
