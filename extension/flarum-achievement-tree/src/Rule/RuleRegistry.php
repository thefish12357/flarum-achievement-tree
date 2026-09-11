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

/**
 * 规则注册表:规则类型 => 策略类。
 *
 * 以后要加新规则(如发帖数、注册天数等),只需实现 RuleInterface
 * 并在此注册,后台会自动出现对应选项与配置表单。
 */
class RuleRegistry
{
    /**
     * @var array<string, class-string<RuleInterface>>
     */
    protected static array $map = [
        'role' => RoleRule::class,
        'post_count' => PostCountRule::class,
        'discussion_count' => DiscussionCountRule::class,
        'days_since_registered' => DaysSinceRegisteredRule::class,
        'likes_received' => LikesReceivedRule::class,
        'conditions' => ConditionsRule::class,
    ];

    public static function get(?string $type): ?RuleInterface
    {
        if (! $type || ! isset(self::$map[$type])) {
            return null;
        }

        return new self::$map[$type];
    }

    /**
     * 返回所有已注册规则的 UI schema,供后台下拉与动态表单使用。
     *
     * @return array<int, array{type:string,label:string,fields:array}>
     */
    public static function all(): array
    {
        $out = [];

        foreach (self::$map as $type => $cls) {
            /** @var RuleInterface $inst */
            $inst = new $cls();
            $out[] = [
                'type' => $inst->type(),
                'label' => $inst->label(),
                'fields' => $inst->fields(),
            ];
        }

        return $out;
    }
}
