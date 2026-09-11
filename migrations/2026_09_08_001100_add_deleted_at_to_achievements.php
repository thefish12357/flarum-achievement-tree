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
        // 软删除:删除成就时只打时间戳,不物理删行,授予记录可追溯
        $schema->table('achievements', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->index();
        });
    },
];
