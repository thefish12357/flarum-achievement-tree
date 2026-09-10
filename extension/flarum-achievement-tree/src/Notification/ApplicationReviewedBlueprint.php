<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source source.
 */

namespace Thefish12357\AchievementTree\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Thefish12357\AchievementTree\AchievementApplication;

/**
 * 成就申请审核结果通知(通过/驳回)→ 申请人通知中心。
 */
class ApplicationReviewedBlueprint implements BlueprintInterface
{
    /**
     * @var AchievementApplication
     */
    protected $application;

    public function __construct(AchievementApplication $application)
    {
        $this->application = $application;
    }

    public function getFromUser()
    {
        // 审核人(未记录审核人时为系统,即 null)
        return $this->application->reviewer;
    }

    public function getSubject()
    {
        return $this->application;
    }

    public function getData()
    {
        return [
            'status' => $this->application->status,
            // 冗余成就名,前端无需加载 subject 关系即可渲染文案
            'achievementName' => $this->application->achievement ? $this->application->achievement->name : '',
            'reviewComment' => $this->application->review_comment,
            'rejectionReason' => $this->application->rejection_reason,
        ];
    }

    public static function getType()
    {
        return 'achievementApplicationReviewed';
    }

    public static function getSubjectModel()
    {
        return AchievementApplication::class;
    }
}
