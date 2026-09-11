<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thefish12357\AchievementTree\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use Thefish12357\AchievementTree\Achievement;

/**
 * 成就相关权限:管理与授予都要求管理员,查看对所有人开放。
 */
class AchievementPolicy extends AbstractPolicy
{
    public function create(User $actor)
    {
        return $this->adminOnly($actor);
    }

    public function edit(User $actor, Achievement $achievement)
    {
        return $this->adminOnly($actor);
    }

    public function delete(User $actor, Achievement $achievement)
    {
        return $this->adminOnly($actor);
    }

    public function award(User $actor, Achievement $achievement)
    {
        return $this->adminOnly($actor);
    }

    public function revoke(User $actor, Achievement $achievement)
    {
        return $this->adminOnly($actor);
    }

    private function adminOnly(User $actor)
    {
        return $actor->isAdmin() ? true : $this->deny();
    }
}
