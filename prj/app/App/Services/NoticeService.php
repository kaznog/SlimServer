<?php
namespace App\Services;

use \App\Models\ResultCode;
use \App\Models\ClusterORM;
use \App\Models\MasterORM;
use \App\App;

/**
 * お知らせ関連処理のサービスクラス.
 */
class NoticeService
{
    /**
     * 既読フラグ付きで、お知らせの一覧を取得する.
     * お知らせはとりあえずキャッシュしない (有効なお知らせ数が多ければ、キャッシュするほうが負荷が大きくなる可能性があるため).
     * @return array
     */
    public function getList($playerId, $limit = 20, $offset = 0, $platform = 0, $created_at = '1000-01-01 00:00:00')
    {
        $datetime = new \Datetime('now');
        $now = $datetime->format('Y-m-d H:i:s');
        $notices = ClusterORM::for_table('notices')
            ->use_replica()
            ->where_lte('start_at', $now)
            ->where_gt('end_at', $now)
            ->where_in('platform', [0,$platform])
            ->where_raw("(withoutnew = 0 OR start_at >= '$created_at')")
            ->offset($offset)
            ->limit($limit)
            ->order_by_desc('start_at')
            ->find_many();
        // 既読チェック.
        if (!empty($notices)) {
            $playersNotices = ClusterORM::for_table('players_notices')
                ->where_equal('player_id', $playerId)
                ->where_in('notice_id', array_keys($notices))
                ->select('notice_id')
                ->find_array();
            $readNoticeIds = [];
            foreach ($playersNotices as $playersNotice) {
                $readNoticeIds[] = $playersNotice['notice_id'];
            }
            foreach ($notices as $id => $notice) {
                $notice->is_read = in_array($id, $readNoticeIds);
            }
        }

        return $notices;
    }

    /**
     * お知らせを既読にする.
     * お知らせにプレゼントがセットされていれば、プレゼントBOXに振り込む.
     * @param  int   $playerId
     * @param  int   $noticeId
     * @return array 振り込まれた PlayersPresentBoxEntry の配列.
     */
    public function setRead($playerId, $noticeId)
    {
        $notice = $this->getNotice($noticeId);
        // 既読チェック.
        $playersNotice = ClusterORM::for_table('players_notices')
                ->where_equal('player_id', $playerId)
                ->where_equal('notice_id', $noticeId)
                ->find_one();
        if ($playersNotice) {
            // 既に既読状態: エラー終了.
            $app = App::getInstance();
            $app->responseArray = ["resultCode" => ResultCode::NOTICE_ALREADY_READ];
            $app->halt(200);
        }

        // 既読にする
        $db = ClusterORM::for_table('players_notices')
            ->select_shard($playerId)
            ->current_db();
        try {
            $db->beginTransaction();
            // 既読にセット
            $playersNotice = ClusterORM::for_table('players_notices')
                ->create(
                    [
                        'player_id' => $playerId,
                        'notice_id' => $noticeId
                    ]
                )->set_expr("created_at", "NOW()");
            $playersNotice->save();

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            $app = App::getInstance();
            $app->logger->addNotice("{$e}");
            if ($e instanceof \Slim\Exception\SlimException) {
                throw $e;
            } else {
                // エラー終了.
                $app->responseArray = ["resultCode" => ResultCode::DB_ERROR];
                $app->halt(500);
            }
        }
    }

    /**
     * 現在有効なお知らせを取得.
     * 見つからなければエラー終了する.
     * @param  int       $noticeId
     * @return MasterORM
     */
    protected function getNotice($noticeId)
    {
        $app = App::getInstance();
        $notice = MasterORM::for_table('notices')->get_one($noticeId);
        if (!$notice) {
            // お知らせがない: エラー終了.
            $app->responseArray = ["resultCode" => ResultCode::NOTICE_NOT_FOUND];
            $app->halt(200);
        }
        $now = new \Datetime('now');
        $startAt = new \Datetime($notice->start_at);
        $endAt = new \Datetime($notice->end_at);
        if ($now < $startAt || $now > $endAt) {
            // お知らせが期間外: エラー終了.
            $app->responseArray = ["resultCode" => ResultCode::NOTICE_NOT_FOUND];
            $app->halt(200);
        }

        return $notice;
    }

    /**
     * ログイン時のお知らせチェック
     * @param  int    $playerId
     * @param  string $created_at
     * @param ClusterORM notice オブジェクト.
     */
    public function checkAtLogin($playerId,$platform,$created_at)
    {
        $mailService = new MailService();
        $notices = $this->getList($playerId,20,0,$platform,$created_at);

        // お知らせ溜まると重い可能性
        foreach ($notices as $notice) {

            if($notice->is_read) continue;

            // メッセージのみならメール送信
            $mailService->sendByNotice($playerId,$notice->body);
            // 既読にする
            $this->setRead($playerId,$notice->id);
        }

    }

}
