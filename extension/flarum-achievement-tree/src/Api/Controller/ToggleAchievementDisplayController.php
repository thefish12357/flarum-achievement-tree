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

use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Thefish12357\AchievementTree\Achievement;

/**
 * 切换当前用户对某个已得成就是否"在帖子内显示"。
 * 只允许操作自己的中间表行,未持有的成就不允许切换。
 */
class ToggleAchievementDisplayController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        if ($actor->isGuest()) {
            throw new ValidationException(['displayed' => '请先登录']);
        }

        // 从路由参数取 id(路径里的 {id}),避免依赖 queryParams 在某些环境下不含路径参数
        $routeParams = (array) $request->getAttribute('routeParameters');
        $id = Arr::get($routeParams, 'id');
        $achievement = Achievement::findOrFail($id);

        // displayed 优先从 URL 查询参数取(前端放在 ?displayed=...),
        // 兼容地从 body 取(若以后改回)。filter_var 同时处理 "true"/"false"/1/0/boolean。
        $source = (array) $request->getQueryParams();
        $parsed = $request->getParsedBody();
        if (is_array($parsed)) {
            $source = array_merge($source, $parsed);
        }
        $displayed = filter_var(Arr::get($source, 'displayed', true), FILTER_VALIDATE_BOOLEAN);

        // 必须是已获得的成就才能切换显示(没有就没意义)
        $has = $achievement->users()->where('users.id', $actor->id)->exists();
        if (! $has) {
            throw new ValidationException(['displayed' => '仅能切换已获得成就是否显示']);
        }

        $achievement->users()->updateExistingPivot($actor->id, [
            'is_displayed' => $displayed,
        ]);

        return new JsonResponse([
            'id' => (int) $achievement->id,
            'isDisplayed' => $displayed,
        ]);
    }
}
