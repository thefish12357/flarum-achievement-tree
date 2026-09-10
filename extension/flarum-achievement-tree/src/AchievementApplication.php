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
 * 用户提交的成就申请(含证明材料)。
 *
 * @property int $id
 * @property int $user_id
 * @property int $achievement_id
 * @property string|null $message
 * @property array|null $proof_files
 * @property string $status  pending|approved|rejected
 * @property int|null $reviewer_id
 * @property \Carbon\Carbon|null $reviewed_at
 * @property string|null $review_comment
 * @property string|null $rejection_reason
 *
 * @property User|null $user
 * @property Achievement|null $achievement
 * @property User|null $reviewer
 */
class AchievementApplication extends AbstractModel
{
    protected $table = 'achievement_applications';

    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';

    protected $casts = [
        'user_id' => 'int',
        'achievement_id' => 'int',
        'reviewer_id' => 'int',
        'proof_files' => 'array',
    ];

    protected $dates = ['reviewed_at', 'created_at', 'updated_at'];

    protected $fillable = [
        'user_id',
        'achievement_id',
        'message',
        'proof_files',
        'status',
        'review_comment',
        'rejection_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function achievement()
    {
        return $this->belongsTo(Achievement::class, 'achievement_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }
}
