<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thefish12357\AchievementTree;

use Flarum\Database\AbstractModel;
use Flarum\User\User;

/**
 * @property int $id
 * @property string $name
 * @property string|null $slug
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $image_url
 * @property int|null $parent_id
 * @property int $position
 * @property bool $is_hidden
 * @property int $points
 * @property string|null $series
 * @property string|null $series_name
 * @property int $tier
 * @property string|null $rule_type
 * @property mixed $rule_config
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property Achievement|null $parent
 * @property \Illuminate\Database\Eloquent\Collection $children
 * @property \Illuminate\Database\Eloquent\Collection $users
 */
class Achievement extends AbstractModel
{
    protected $table = 'achievements';

    protected $casts = [
        'parent_id' => 'int',
        'position' => 'int',
        'points' => 'int',
        'series' => 'string',
        'series_name' => 'string',
        'tier' => 'int',
        'is_hidden' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'image_url',
        'parent_id',
        'position',
        'is_hidden',
        'points',
        'series',
        'series_name',
        'tier',
        'rule_type',
        'rule_config',
    ];

    /**
     * 把同一 series 下所有成就的显示名称统一设为 $name(实现"修改系列名称")。
     * 使用 Eloquent 而非 DB Facade,避免在序列化上下文中 Facade 根未设置。
     */
    public static function syncSeriesName(string $series, ?string $name): void
    {
        static::where('series', $series)->update(['series_name' => $name]);
    }

    /**
     * 取该 series 当前已有的显示名称,供新建成就时继承,避免同名系列出现两个名字。
     */
    public static function existingSeriesName(string $series): ?string
    {
        $row = static::where('series', $series)
            ->whereNotNull('series_name')
            ->where('series_name', '<>', '')
            ->first();

        return $row ? $row->series_name : null;
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'achievement_user')
            ->withPivot('awarded_at', 'awarded_by_id', 'is_displayed');
    }
}
