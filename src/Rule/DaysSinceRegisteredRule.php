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

use Carbon\Carbon;
use Flarum\User\User;

/**
 * 注册满天数规则:用户注册距今达到配置天数即满足。
 * 配置:['days' => <int>]
 */
class DaysSinceRegisteredRule implements RuleInterface
{
    public function type(): string
    {
        return 'days_since_registered';
    }

    public function label(): string
    {
        return '注册满天数';
    }

    public function fields(): array
    {
        return [
            [
                'key' => 'days',
                'label' => '天数',
                'type' => 'number',
                'required' => true,
            ],
        ];
    }

    public function evaluate(User $user, array $config): bool
    {
        $days = (int) ($config['days'] ?? 0);

        if ($days <= 0 || ! $user->created_at) {
            return false;
        }

        return Carbon::instance($user->created_at)->diffInDays(Carbon::now()) >= $days;
    }
}
