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
 * 自动解锁规则策略接口。
 *
 * 新增一种规则类型:实现本接口并在 RuleRegistry 注册即可,
 * 后台「解锁方式」下拉与动态配置表单会自动出现,无需改前端。
 */
interface RuleInterface
{
    /**
     * 规则类型标识,存于 achievements.rule_type。
     */
    public function type(): string;

    /**
     * 后台展示名称(中文)。
     */
    public function label(): string;

    /**
     * 该规则需要的配置字段 schema,供后台动态渲染表单。
     * 例:[['key' => 'role_id', 'label' => '角色', 'type' => 'role', 'required' => true]]
     */
    public function fields(): array;

    /**
     * 给定用户与配置,判断是否满足规则。
     */
    public function evaluate(User $user, array $config): bool;
}
