<?php
namespace App\Services;

use \App\Models\Maintenance;
use \App\App;

/**
 * メンテナンス関係処理.
 */
class MaintenanceService
{
    /**
     * 現在メンテナンス中かどうかチェックする.
     * @return ClusterORM メンテナンス中であればClusterORM. そうでなければ false.
     */
    public function isUnderMaintenance()
    {
        $maintenance = Maintenance::get();
        $startAt = $this->getTimestamp($maintenance->start_at);
        $endAt = $this->getTimestamp($maintenance->end_at);

        // 現在時刻.
        $now = new \Datetime();
        $now = $now->getTimestamp();

        // $startAt, $endAt が共に null であれば、メンテナンス中ではない.
        if ($startAt == null && $endAt == null) {
            return false;
        }
        if ($startAt && $startAt < $now) {
            if ($endAt && $endAt < $now) {
                // $startAt が条件を見たしていても、$endAt が条件を満たしていなければ、メンテナンス中ではない.
                return false;
            }
        } elseif ($endAt && $endAt > $now) {
            if ($startAt && $startAt > $now) {
                // $endAt が条件を見たしていても、$startAt が条件を満たしていなければ、メンテナンス中ではない.
                return false;
            }
        }

        return $maintenance;
    }

    /**
     * 時刻文字列からタイムスタンプを取得する.
     * @param  string $str
     * @return int    タイムスタンプ. $str が空であれば、null;
     */
    private function getTimestamp($str)
    {
        $timestamp = null;
        $datetime = $str ? new \Datetime($str) : null;
        if ($datetime) {
            $timestamp = $datetime->getTimestamp();
        }

        return $timestamp;
    }

    /**
     * メンテナンス中でもアクセス可能なAPIであるかチェック.
     * @param  string  $requestPath
     * @return boolean
     */
    public function isAllowedApi($requestPath)
    {
//        $allowedApis = ["\/api\/app\/check_version", "\/api\/sample\/", "\/api\/debug\/"];
        $allowedApis = ["\/api\/sample\/", "\/api\/debug\/"];
        $allowedApis = array_map(
            function ($lf) {
                return "(" . $lf . ")";
            },
            $allowedApis
        );
        $regexp = "/^" . join($allowedApis, "|") . "/";

        return (preg_match($regexp, $requestPath) === 1);
    }

}
