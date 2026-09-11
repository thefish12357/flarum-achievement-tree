<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thefish12357\AchievementTree\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\UserSerializer;
use InvalidArgumentException;
use Thefish12357\AchievementTree\AchievementApplication;

class AchievementApplicationSerializer extends AbstractSerializer
{
    protected $type = 'achievement-applications';

    /**
     * @param AchievementApplication $application
     */
    protected function getDefaultAttributes($application): array
    {
        if (! ($application instanceof AchievementApplication)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.AchievementApplication::class
            );
        }

        return [
            'userId' => (int) $application->user_id,
            'achievementId' => (int) $application->achievement_id,
            'message' => $application->message,
            'proofFiles' => $application->proof_files ?? [],
            'proofImages' => $application->proof_images ?? [],
            'status' => $application->status,
            'reviewComment' => $application->review_comment,
            'rejectionReason' => $application->rejection_reason,
            'reviewedAt' => $this->formatDate($application->reviewed_at),
            'createdAt' => $this->formatDate($application->created_at),
            'canReview' => $this->actor->can('review', $application),
        ];
    }

    protected function user($application)
    {
        return $this->hasOne($application, UserSerializer::class);
    }

    protected function achievement($application)
    {
        return $this->hasOne($application, AchievementSerializer::class);
    }

    protected function reviewer($application)
    {
        return $this->hasOne($application, UserSerializer::class);
    }
}
