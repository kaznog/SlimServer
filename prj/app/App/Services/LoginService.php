<?php
namespace App\Services;

use \App\Models\ResultCode;
use \App\Models\LoginResult;
use \App\Models\ClusterORM;
use \App\Models\PlayersIdentity;
use \App\Models\Player;
use \App\Models\PlayersDailyActivity;
use \App\Models\LoginBonus;
use \App\Models\Town;
use \Utils\Str;
use \App\App;
use \App\Services\PlayerService;

/**
 * ログイン関連処理のサービスクラス.
 */
class LoginService
{
    /**
     * ログイン.
     * @param $user_id SQEX Bridge user id
     * @return LoginResult ログイン結果オブジェクト
     */
    public function login($user_id)
    {
        $app = App::getInstance();

        // UUIDが空文字列であればエラー.
        if (strlen($user_id) == 0) {
            $app->responseArray = ["resultCode" => ResultCode::INSUFFICIENT_PARAMETERS];
            $app->halt(200);
        }
        $playersIdentity = PlayersIdentity::findByUserId($user_id);
        $app->logger->addDebug(__CLASS__ . "::" . __FUNCTION__ . "::" . __LINE__ . " identitie: " . serialize($playersIdentity));

        $loginResult = new LoginResult();

        if ($playersIdentity !== false) {
            // BANされていればエラー
            if ($playersIdentity->status & PlayersIdentity::STATUS_FLAG_BAN) {
                $app->responseArray = ["resultCode" => ResultCode::PLAYER_BANNED];
                $app->halt(200);
            }

            $app->logger->addDebug(__CLASS__ . "::" . __FUNCTION__ . "::" . __LINE__);
            $playerId = $playersIdentity->id;
            $loginResult->playerId = $playerId;

            $db = ClusterORM::for_table('players')
                ->select_shard($playerId)
                ->current_db();

            $firstLoginOfTheDay = false;
            try {
                $db->beginTransaction();

                // players.session_idを同時に書き換えると消せないセッションIDが出てくる（複数端末同時に端末移行するなど）ので、for updateする
                $player = ClusterORM::for_table('players')
                    ->select_shard($playerId)
                    ->raw_query("select * from players where id = ? for update",[$playerId])
                    ->find_one();
                if ($player === false) {
                    // エラー： プレイヤーが存在しない
                    $app->responseArray = ["resultCode" => ResultCode::PLAYER_NOT_FOUND];
                    $app->halt(200);
                }

                // セッションID発行.
                $sessionId = RequestVerifyService::issueSessionId($playerId);
                $app->logger->addDebug(__CLASS__ . "::" . __FUNCTION__ . " issueSessionId: " . $sessionId);
                if ($sessionId === false) {
                    // エラー：memcached set error
                    $app->responseArray = ["resultCode" => ResultCode::MEMCACHED_SET_ERROR];
                    $app->halt(200);
                }
                $loginResult->sessionId = $sessionId;
                if (!empty($player->session_id)) {
                    // 前回のセッションが残っていれば削除 多重ログインは出来ない
                    RequestVerifyService::invalidateSessionId($player->session_id); // セッション破棄.
                }
                $player->session_id = $sessionId;

                // ソーシャル関係ボーナス振込.
                $loginResult->socialBonus = $this->applySocialBonus($player, $playersIdentity);
                // 最終ログイン時刻、ログインボーナス保存.
                $lastLoginAt = Str::strToTime($player->last_login_at);
                $lastBod = PlayerService::calcLastBeginningOfDay();
                if ($lastLoginAt < $lastBod) {
                    // 本日初ログイン.
                    $playersIdentity->set_expr('last_login_at', 'NOW()'); // players_identities.last_login_at
                    if (isset($_SERVER['HTTP_USER_AGENT'])) {
                        $playersIdentity->set('useragent',substr($_SERVER['HTTP_USER_AGENT'],0,128));
                    }
                    $player->login_count += 1;
                    $player->login_streak_count += 1;
                    list($loginBonus) = $this->applyLoginBonus($player);
                    $loginResult->loginBonus = $loginBonus;
                }
                $player->set_expr('updated_at', 'NOW()');
                $player->set_expr('last_login_at', 'NOW()'); // players.last_login_at
                $player->save();

                // players_daily_activities 保存.
                $now = new \Datetime();
                $playersDailyActivity = PlayersDailyActivity::get($playersIdentity, $now->format('Y-m-d'));
                $playersDailyActivity->save();

                $db->commit();
            } catch (\Exception $e) {
                $db->rollback();
                $app = App::getInstance();
                $app->logger->addNotice("{$e}");
                if ($e instanceof \Slim\Exception\SlimException) {
                    throw $e;
                } else {
                    // エラー終了.
                    $app->responseArray = ["resultCode" => ResultCode::UNKNOWN_ERROR];
                    $app->halt(500);
                }
            }

            // 値が変更されている場合にだけ更新されるはず.
            $playersIdentity->save();

            // お知らせチェック
            $noticeService = new NoticeService();
            $noticeService->checkAtLogin($playerId, $playersIdentity->device_platform, $playersIdentity->created_at);

            // メール数取得.
            $mailService = new MailService();
            $loginResult->mailCount = $mailService->getUnreadCount($playerId);

            $town = new Town($player->town_id);
            $loginResult->LivingTown = $town->toArray();
        }

        return $loginResult;
    }

    /**
     * ソーシャル関係ボーナスを振り込む.
     * この時点では保存は行わない.
     * @param  ClusterORM $player
     * @param ClusterORM $playersIdentity
     * @return array      振り込まれたソーシャル関係ボーナス
     */
    protected function applySocialBonus($player, $playersIdentity)
    {
        // ソーシャル関係ボーナス
        $playersSocialActivity = ClusterORM::for_table('players_social_activities')
            ->where_equal('player_id', $player->id)
            ->find_one();
        $socialBonus = false;
        if ($playersSocialActivity) {
            if ($playersSocialActivity->dummy_reward_exp > 0) {
                $playerService = new PlayerService();
                $playerService->addExp($playersIdentity, $player, $playersSocialActivity->dummy_reward_exp);
            }
            $socialBonus = [
                'likeCnt' => $playersSocialActivity->like_count,
                'likeFriendPt' => $playersSocialActivity->like_friend_point,
            ];
            Player::addFriendPoint($player, $playersSocialActivity->like_friend_point);
            // リセット.
            $playersSocialActivity->like_count = 0;
            $playersSocialActivity->like_friend_point = 0;
            $playersSocialActivity->save();
        }

        return $socialBonus;
    }

    /**
     * ログインボーナスの適用.
     * @param $player ORM経由で取得したプレイヤーオブジェクト.
     * @return array [LoginBonus] 他にボーナスの種類が増えた場合のためにarray
     */
    protected function applyLoginBonus($player)
    {
        $loginBonus = LoginBonus::getLoginBonus($player->login_streak_count);
        // ボーナス適用.
        if ($loginBonus) {
            if ($loginBonus->friend_point) {
                Player::addFriendPoint($player, $loginBonus->friend_point);
            }
        }

        return [$loginBonus];
    }


}
