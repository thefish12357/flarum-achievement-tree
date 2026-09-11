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

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Thefish12357\AchievementTree\Api\Serializer\RuleTypeSerializer;
use Thefish12357\AchievementTree\Rule\RuleRegistry;
use Tobscure\JsonApi\Document;

/**
 * 返回所有已注册自动解锁规则的 schema,供后台「解锁方式」下拉与动态表单使用。
 * 仅管理员可访问。
 */
class ListRuleTypesController extends AbstractListController
{
    public $serializer = RuleTypeSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        return RuleRegistry::all();
    }
}
