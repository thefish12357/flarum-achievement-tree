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
use Thefish12357\AchievementTree\AchievementApplication;

/**
 * 成就申请相关权限:登录用户可提交,管理员可审核。
 */
class ApplicationPolicy extends AbstractPolicy
{
    public function create(User $actor)
    {
        return $actor->isGuest() ? $this->deny() : true;
    }

    public function review(User $actor, AchievementApplication $application)
    {
        return $actor->isAdmin() ? true : $this->deny();
    }
}
