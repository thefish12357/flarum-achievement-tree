<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thefish12357\AchievementTree;

use Flarum\Database\AbstractModel;
use Flarum\User\User;

/**
 * 上传图片资源登记表:每张图都有上传者与类型,可追溯、可管理。
 *
 * @property int $id
 * @property string $type  badge|proof
 * @property string $path  相对所属磁盘根目录的存储路径
 * @property string|null $original_name
 * @property string|null $mime_type
 * @property int $size
 * @property int $user_id  上传者
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property User|null $user
 */
class AchievementImage extends AbstractModel
{
    protected $table = 'achievement_images';

    const TYPE_BADGE = 'badge';
    const TYPE_PROOF = 'proof';

    protected $casts = [
        'user_id' => 'int',
        'size' => 'int',
    ];

    protected $fillable = [
        'type',
        'path',
        'original_name',
        'mime_type',
        'size',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
