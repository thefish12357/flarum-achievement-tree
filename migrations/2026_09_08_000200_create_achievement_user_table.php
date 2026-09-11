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
        $schema->create('achievement_user', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('achievement_id')->unsigned();
            $table->dateTime('awarded_at')->nullable();
            $table->integer('awarded_by_id')->unsigned()->nullable();

            $table->unique(['user_id', 'achievement_id']);
            $table->index('achievement_id');
        });
    },
];
