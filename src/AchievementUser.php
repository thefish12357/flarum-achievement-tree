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

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * 用户获得成就的中间表模型。
 *
 * @property int $id
 * @property int $user_id
 * @property int $achievement_id
 * @property \Carbon\Carbon|null $awarded_at
 * @property int|null $awarded_by_id
 */
class AchievementUser extends Pivot
{
    protected $table = 'achievement_user';

    public $incrementing = true;

    protected $casts = [
        'user_id' => 'int',
        'achievement_id' => 'int',
        'awarded_by_id' => 'int',
    ];

    protected $dates = ['awarded_at'];
}
