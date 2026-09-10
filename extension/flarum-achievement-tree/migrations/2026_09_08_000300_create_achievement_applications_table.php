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
        $schema->create('achievement_applications', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('achievement_id')->unsigned();
            $table->text('message')->nullable();

            // 证明材料:JSON 数组,元素为图片 URL
            $table->text('proof_files')->nullable();

            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->integer('reviewer_id')->unsigned()->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index(['user_id', 'achievement_id']);
        });
    },

    'down' => function (Builder $schema) {
        $schema->dropIfExists('achievement_applications');
    },
];
