<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thefish12357\AchievementTree\Rule;

use Flarum\User\User;

/**
 * 角色规则:用户拥有指定角色(group)即满足。
 * 配置:['role_id' => <int>]
 */
class RoleRule implements RuleInterface
{
    public function type(): string
    {
        return 'role';
    }

    public function label(): string
    {
        return '拥有指定角色';
    }

    public function fields(): array
    {
        return [
            [
                'key' => 'role_id',
                'label' => '角色',
                'type' => 'role',
                'required' => true,
            ],
        ];
    }

    public function evaluate(User $user, array $config): bool
    {
        $roleId = $config['role_id'] ?? null;

        if ($roleId === null) {
            return false;
        }

        return $user->groups()->where('groups.id', $roleId)->exists();
    }
}
