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

use Flarum\Post\Post;
use Flarum\User\User;

/**
 * 获得点赞数规则:用户发布的帖子累计获得的点赞数达到配置值即满足。
 * 依赖 flarum/likes 扩展提供的 Post.likes 关系,若未安装该扩展则永不触发。
 * 配置:['likes' => <int>]
 */
class LikesReceivedRule implements RuleInterface
{
    public function type(): string
    {
        return 'likes_received';
    }

    public function label(): string
    {
        return '获得点赞数达到';
    }

    public function fields(): array
    {
        return [
            [
                'key' => 'likes',
                'label' => '点赞数',
                'type' => 'number',
                'required' => true,
            ],
        ];
    }

    public function evaluate(User $user, array $config): bool
    {
        $min = (int) ($config['likes'] ?? 0);

        if ($min <= 0) {
            return false;
        }

        // 未安装 flarum/likes 时 Post 模型没有 likes 关系,直接判定不满足
        if (! method_exists(Post::class, 'likes')) {
            return false;
        }

        $count = Post::query()
            ->where('user_id', $user->id)
            ->whereNull('hidden_at')
            ->withCount('likes')
            ->get()
            ->sum('likes_count');

        return (int) $count >= $min;
    }
}
