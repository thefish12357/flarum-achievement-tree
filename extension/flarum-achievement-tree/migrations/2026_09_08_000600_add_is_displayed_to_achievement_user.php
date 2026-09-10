<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if ($schema->hasColumn('achievement_user', 'is_displayed')) {
            return;
        }

        $schema->table('achievement_user', function (Blueprint $table) {
            // 用户对每个已获得成就是否"在帖子内显示",默认显示
            $table->boolean('is_displayed')->default(true)->after('awarded_by_id');
        });
    },

    'down' => function (Builder $schema) {
        if (! $schema->hasColumn('achievement_user', 'is_displayed')) {
            return;
        }

        $schema->table('achievement_user', function (Blueprint $table) {
            $table->dropColumn('is_displayed');
        });
    },
];
