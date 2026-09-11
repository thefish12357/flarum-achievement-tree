<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if ($schema->hasColumn('achievements', 'series_sort')) {
            return;
        }

        $schema->table('achievements', function ($table) {
            // 系列排序:数字越小越靠前(0 最靠左/最前)
            $table->integer('series_sort')->default(0)->after('series_name');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('achievements', function ($table) {
            $table->dropColumn('series_sort');
        });
    },
];
