<?php

/*
 * This file is part of thefish12357/flarum-achievement-tree.
 *
 * Copyright (c) 2026 thefish12357.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thefish12357\AchievementTree\Support;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * 上传目录的后台配置读取与清洗。
 * 目录存放在 storage 下(非 public 直出),统一由扩展的服务路由对外提供。
 */
class UploadSettings
{
    public const BADGES_PATH = 'thefish12357-achievement-tree.badges_path';
    public const PROOFS_PATH = 'thefish12357-achievement-tree.proofs_path';

    /** storage 内的根目录 */
    public const STORAGE_ROOT = 'achievement-tree';

    public static function badgesPath(SettingsRepositoryInterface $settings): string
    {
        return static::sanitize($settings->get(static::BADGES_PATH), 'badges');
    }

    public static function proofsPath(SettingsRepositoryInterface $settings): string
    {
        return static::sanitize($settings->get(static::PROOFS_PATH), 'proofs');
    }

    /**
     * 只允许字母数字下划线中划线与斜杠,禁止穿越与非法字符;空值回落默认。
     */
    private static function sanitize($value, string $fallback): string
    {
        $value = trim((string) $value, "/\\");
        $value = preg_replace('#[^A-Za-z0-9_\-/]#', '', $value);

        return $value !== '' ? $value : $fallback;
    }
}
