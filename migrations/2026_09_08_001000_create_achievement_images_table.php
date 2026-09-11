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
        // 图片资源统一登记:记录上传者/类型/大小,便于追溯与管理
        $schema->create('achievement_images', function (Blueprint $table) {
            $table->increments('id');
            // badge = 成就徽章图;proof = 申请证明材料图
            $table->string('type', 20);
            // 相对于所属磁盘根目录的存储路径(storage 内,不经 public 直接暴露)
            $table->string('path', 255);
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->integer('size')->unsigned()->default(0);
            // 上传者
            $table->integer('user_id')->unsigned();

            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
        });
    },
];
