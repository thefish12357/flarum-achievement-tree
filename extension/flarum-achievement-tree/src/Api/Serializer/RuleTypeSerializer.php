<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thefish12357\AchievementTree\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;

/**
 * 把 RuleRegistry::all() 的规则 schema 序列化为前端可用的下拉/表单数据。
 */
class RuleTypeSerializer extends AbstractSerializer
{
    protected $type = 'achievement-rule-types';

    protected function getDefaultAttributes($rule): array
    {
        return [
            'ruleType' => $rule['type'],
            'label' => $rule['label'],
            'fields' => $rule['fields'],
        ];
    }

    public function getId($rule)
    {
        return $rule['type'];
    }
}
