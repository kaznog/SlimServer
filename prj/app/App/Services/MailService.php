<?php
namespace App\Services;

use \App\Models\ResultCode;
use \App\Models\ClusterORM;
use \App\Models\Mail;
use \App\Models\Friendship;
use \App\App;

/**
 * メール関連処理のサービスクラス.
 */
class MailService
{
    // 運営扱いの player id
    const PLAYER_ID_MANAGEMENT = 1;

    /**
     * 指定したプレイヤー宛のメールの一覧を、新しい順に取得する.
     * @param  int   $playerId   受信プレイヤーID
     * @param  int   $offset
     * @param  int   $limit
     * @param  bool  $onlyUnread
     * @return array 送信者PlayerIdentity と Mail のペアの配列.
     */
    public function getMails($playerId, $offset = 0, $limit = 20, $onlyUnread = false)
    {
        $orm = ClusterORM::for_table('mails')
                ->where_equal('player_id', $playerId)
                ->order_by_desc('created_at')
                ->offset($offset)
                ->limit($limit);
        if ($onlyUnread) {
            // 未読のみ.
            $mails = $orm->where_equal('is_read', Mail::IS_NOT_READ)->find_many();
        } else {
            $mails = $orm->find_many();
        }
        $result = [];
        if (!empty($mails)) {
            // 送信者PlayerIdentityオブジェクトを取得.
            $senderPlayerIds = array_map(
                function ($mail) {
                    return $mail->sender_player_id;
                },
                $mails
            );
            $senderPlayerIds = array_unique($senderPlayerIds);
            $playersIdentities = ClusterORM::for_table('players_identities')
                ->where_in('id', $senderPlayerIds)
                ->find_many();
            foreach ($mails as $mail) {
                $result[] = [$playersIdentities[$mail->sender_player_id], $mail];
            }
        }

        return $result;
    }

    /**
     * 未読メール数を取得する.
     * @param  int $playerId 受信プレイヤーID
     * @return int 未読メール数.
     */
    public function getUnreadCount($playerId)
    {
        $unreadCount = ClusterORM::for_table('mails')
            ->select_shard($playerId)
            ->raw_query( 'SELECT COUNT(id) AS count FROM (SELECT id FROM mails WHERE player_id = ? AND is_read = ? LIMIT '.Mail::MAX_LIMIT.') AS A;', [$playerId, Mail::IS_NOT_READ] )
            ->find_one();

        return $unreadCount->count;
    }

    /**
     * メールを取得して、既読にセットする.
     * @param  int   $playerId 受信プレイヤーID
     * @param  int   $id       メールID
     * @return array Mailオブジェクト.
     */
    public function get($playerId, $id)
    {
        $mail = ClusterORM::for_table('mails')
                ->where_equal('player_id', $playerId)
                ->find_one($id);
        if (!$mail) {
            // メールが見つからなければエラー終了.
            $app = App::getInstance();
            $app->responseArray = ["resultCode" => ResultCode::MAIL_NOT_FOUND];
            $app->halt(200);
        }
        // 既読にセットする.
        if (!$mail->is_read) {
            $mail->set('is_read', Mail::IS_READ);
            $mail->save();
        }

        return $mail;
    }

    /**
     * メールを送信する.
     * @param  int    $playerId       送信先プレイヤーID
     * @param  int    $senderPlayerId 送信元プレイヤーID
     * @param  string $body           本文
     * @param  int    $battleFieldId
     * @param  int    $side
     * @return Mail   オブジェクト.
     */
    public function send($playerId, $senderPlayerId, $body, $battleFieldId, $side)
    {
        $app = App::getInstance();
        // 送信先確認.
        $playersIdentity = ClusterORM::for_table('players_identities')
            ->find_one($playerId);
        if (! $playersIdentity) {
            // 送信先が存在しなければエラー終了.
            $app->responseArray = ["resultCode" => ResultCode::PLAYER_NOT_FOUND];
            $app->halt(200);
        }
        // 本文の長さチェック
        if ( mb_strlen($body, 'utf-8') > 255 ) {
            $app->responseArray = ["resultCode" => ResultCode::MAIL_TOO_LONG];
            $app->halt(200);
        }
        // 相手がフレンドかどうか確認.
        $friendship = Friendship::getFriendship($playerId, $senderPlayerId);
        if (!$friendship || $friendship->state != Friendship::STATE_ACCEPTED) {
            // 送信先がフレンドでなければエラー終了.
            $app->responseArray = ["resultCode" => ResultCode::MAIL_DESTINATION_NOT_FRIEND];
            $app->halt(200);
        }
        // メール作成
        $mail = ClusterORM::for_table('mails')
            ->create(
                [
                    'player_id' => $playerId,
                    'sender_player_id' => $senderPlayerId,
                    'body' => $body,
                    'battle_field_id' => $battleFieldId,
                    'side' => $side,
                    'is_read' => Mail::IS_NOT_READ
                ]
            )->set_expr('created_at', 'NOW()');
        $mail->save();

        return $mail;
    }

    /**
     * お知らせ情報からメールを送信する.
     * @param  int    $playerId 送信先プレイヤーID
     * @param  string $body     本文
     * @return Mail   オブジェクト.
     */
    public function sendByNotice($playerId, $body)
    {
        // 送信先確認.
        $playersIdentity = ClusterORM::for_table('players_identities')
            ->find_one($playerId);
        if (! $playersIdentity) {
            // 送信先が存在しなければエラー終了.
            return false;
        }

        // メール作成
        $mail = ClusterORM::for_table('mails')
            ->create(
                [
                    'player_id' => $playerId,
                    'sender_player_id' => self::PLAYER_ID_MANAGEMENT,
                    'body' => $body,
                    'battle_field_id' => 0,
                    'side' => 0,
                    'is_read' => Mail::IS_NOT_READ
                ]
            )->set_expr('created_at', 'NOW()');
        $mail->save();

        return $mail;
    }

}
