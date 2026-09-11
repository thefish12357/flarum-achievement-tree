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
        if ($schema->hasColumn('achievement_applications', 'proof_images')) {
            return;
        }

        $schema->table('achievement_applications', function (Blueprint $table) {
            // 申请证明材料图片(用户上传,JSON 数组存本站图片服务路由或旧版 assets 路径)
            $table->json('proof_images')->nullable()->after('proof_files');
        });
    },
];
