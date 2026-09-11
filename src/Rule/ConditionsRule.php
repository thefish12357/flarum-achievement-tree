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
 * 自定义条件组合规则:管理员在后台手动添加多个条件,全部满足才自动授予。
 * 配置:['conditions' => [['type' => 'post_count', 'posts' => 10], ['type' => 'days_since_registered', 'days' => 30], ...]]
 * 各子条件复用 RuleRegistry 中已注册的规则,便于扩展。
 */
class ConditionsRule implements RuleInterface
{
    public function type(): string
    {
        return 'conditions';
    }

    public function label(): string
    {
        return '自定义条件组合(全部满足)';
    }

    public function fields(): array
    {
        // 多条件编辑器由后台前端自绘,这里无需 schema
        return [];
    }

    public function evaluate(User $user, array $config): bool
    {
        $conditions = $config['conditions'] ?? [];

        if (! is_array($conditions) || count($conditions) === 0) {
            return false;
        }

        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                return false;
            }

            $type = $condition['type'] ?? null;
            unset($condition['type']);

            $rule = RuleRegistry::get($type);

            if (! $rule) {
                return false;
            }

            if (! $rule->evaluate($user, is_array($condition) ? $condition : [])) {
                return false;
            }
        }

        return true;
    }
}
