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
        if ($schema->hasColumn('achievements', 'series')) {
            return;
        }

        $schema->table('achievements', function (Blueprint $table) {
            // 系列标识(如 "A"、"B"),用于后台分组与帖子内按系列去重显示
            $table->string('series', 100)->nullable()->after('image_url');
            // 层级:同系列内越大越高,用户获得高层级后低层级在帖子隐藏
            $table->integer('tier')->unsigned()->default(0)->after('series');
        });
    },
];
