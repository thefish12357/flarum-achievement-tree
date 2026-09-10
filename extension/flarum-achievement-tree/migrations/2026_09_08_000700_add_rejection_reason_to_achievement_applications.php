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
        if ($schema->hasColumn('achievement_applications', 'rejection_reason')) {
            return;
        }

        $schema->table('achievement_applications', function (Blueprint $table) {
            // 驳回理由:仅驳回时填写,与审核备注(review_comment)区分
            $table->string('rejection_reason', 255)->nullable()->after('review_comment');
        });
    },

    'down' => function (Builder $schema) {
        if (! $schema->hasColumn('achievement_applications', 'rejection_reason')) {
            return;
        }

        $schema->table('achievement_applications', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    },
];
