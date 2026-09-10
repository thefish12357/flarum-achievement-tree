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
        if ($schema->hasColumn('achievements', 'series_name')) {
            return;
        }

        $schema->table('achievements', function (Blueprint $table) {
            // 系列的显示名称,后台可修改;同一 series 下所有成就共享,
            // 留空时回退显示 series 标识(如 "A")
            $table->string('series_name', 150)->nullable()->after('series');
        });
    },

    'down' => function (Builder $schema) {
        if (! $schema->hasColumn('achievements', 'series_name')) {
            return;
        }

        $schema->table('achievements', function (Blueprint $table) {
            $table->dropColumn('series_name');
        });
    },
];
