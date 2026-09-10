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
        $schema->create('achievements', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 150);
            $table->string('slug', 150)->nullable();
            $table->text('description')->nullable();

            // 图标:优先 Font Awesome 类名,其次自定义图片
            $table->string('icon', 100)->nullable();
            $table->string('image_url', 255)->nullable();

            // 树形结构:自关联
            $table->integer('parent_id')->unsigned()->nullable();
            $table->integer('position')->unsigned()->default(0);
            $table->boolean('is_hidden')->default(0);
            $table->integer('points')->unsigned()->default(0);

            // 预留:第二阶段自动解锁规则
            $table->string('rule_type', 100)->nullable();
            $table->text('rule_config')->nullable();

            $table->timestamps();

            $table->index('parent_id');
        });
    },

    'down' => function (Builder $schema) {
        $schema->dropIfExists('achievements');
    },
];
