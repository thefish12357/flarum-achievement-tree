<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thefish12357\AchievementTree\Api\Controller;

use Carbon\Carbon;
use Flarum\Api\Controller\AbstractShowController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\Notification\NotificationSyncer;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Thefish12357\AchievementTree\AchievementApplication;
use Thefish12357\AchievementTree\Api\Serializer\AchievementApplicationSerializer;
use Thefish12357\AchievementTree\Notification\ApplicationReviewedBlueprint;
use Tobscure\JsonApi\Document;

class ReviewApplicationController extends AbstractShowController
{
    public $serializer = AchievementApplicationSerializer::class;

    public $include = ['user', 'achievement'];

    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');

        /** @var AchievementApplication $application */
        $application = AchievementApplication::findOrFail($id);

        $actor->assertCan('review', $application);

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);
        $status = Arr::get($data, 'status');

        if (! in_array($status, [AchievementApplication::APPROVED, AchievementApplication::REJECTED], true)) {
            throw new ValidationException(['status' => '审核状态只能是 approved 或 rejected']);
        }

        $application->status = $status;
        $application->reviewer_id = $actor->id;
        $application->reviewed_at = Carbon::now();
        $application->review_comment = Arr::get($data, 'reviewComment');
        $application->rejection_reason = Arr::get($data, 'rejectionReason');
        $application->save();

        // 审核通过即自动授予成就
        if ($status === AchievementApplication::APPROVED && $application->achievement) {
            $achievement = $application->achievement;

            if (! $achievement->users()->where('users.id', $application->user_id)->exists()) {
                $achievement->users()->attach($application->user_id, [
                    'awarded_at' => Carbon::now(),
                    'awarded_by_id' => $actor->id,
                ]);
            }
        }

        // 审核结果(通过/驳回)推送申请人通知中心
        $this->notifications->sync(
            new ApplicationReviewedBlueprint($application),
            [$application->user]
        );

        return $application;
    }
}
