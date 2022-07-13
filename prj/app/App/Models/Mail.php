<?php
namespace App\Models;

/**
 * メールモデル.
 */
class Mail
{
    // 未読状態.
    const IS_NOT_READ = 0;
    // 既読状態.
    const IS_READ = 1;

    // 一覧取得件数最大値
    const MAX_LIMIT = 50;
    // 一覧取得件数デフォルト値
    const DEFAULT_LIMIT = 20;
}
