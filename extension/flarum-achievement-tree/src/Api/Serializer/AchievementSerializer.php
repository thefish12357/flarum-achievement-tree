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
use InvalidArgumentException;
use Thefish12357\AchievementTree\Achievement;

class AchievementSerializer extends AbstractSerializer
{
    protected $type = 'achievements';

    /**
     * @param Achievement $achievement
     */
    protected function getDefaultAttributes($achievement): array
    {
        if (! ($achievement instanceof Achievement)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Achievement::class
            );
        }

        return [
            'name' => $achievement->name,
            'slug' => $achievement->slug,
            'description' => $achievement->description,
            'icon' => $achievement->icon,
            'imageUrl' => $achievement->image_url,
            'parentId' => $achievement->parent_id,
            'position' => (int) $achievement->position,
            'isHidden' => (bool) $achievement->is_hidden,
            'series' => $achievement->series,
            'seriesName' => $achievement->series_name,
            'tier' => (int) $achievement->tier,
            'ruleType' => $achievement->rule_type,
            'createdAt' => $this->formatDate($this->toDateTime($achievement->created_at)),
            'canEdit' => $this->actor->can('edit', $achievement),
            'canAward' => $this->actor->can('award', $achievement),
        ];
    }

    /**
     * 将 created_at/updated_at 统一转成 DateTime,兼容模型强转未生效(拿到字符串)的情况。
     */
    protected function toDateTime($value): ?\DateTime
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTime) {
            return $value;
        }

        if (is_string($value)) {
            return new \DateTime($value);
        }

        if (is_numeric($value)) {
            return new \DateTime('@'.$value);
        }

        return null;
    }

    protected function parent($achievement)
    {
        return $this->hasOne($achievement, self::class);
    }

    protected function children($achievement)
    {
        return $this->hasMany($achievement, self::class);
    }
}
