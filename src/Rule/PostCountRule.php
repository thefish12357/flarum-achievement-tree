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
 * 累计发帖数(评论)规则:用户 comment_count 达到配置值即满足。
 * 配置:['posts' => <int>]
 */
class PostCountRule implements RuleInterface
{
    public function type(): string
    {
        return 'post_count';
    }

    public function label(): string
    {
        return '累计发帖数达到';
    }

    public function fields(): array
    {
        return [
            [
                'key' => 'posts',
                'label' => '发帖数',
                'type' => 'number',
                'required' => true,
            ],
        ];
    }

    public function evaluate(User $user, array $config): bool
    {
        $min = (int) ($config['posts'] ?? 0);

        if ($min <= 0) {
            return false;
        }

        return (int) ($user->comment_count ?? 0) >= $min;
    }
}
