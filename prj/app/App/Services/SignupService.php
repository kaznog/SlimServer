<?php
namespace App\Services;

use \App\Models\ResultCode;
use \App\Models\ClusterORM;
use \App\Models\PlayersIdentity;
use \App\App;

/**
 * サインアップ処理のサービスクラス.
 */
class SignupService
{
    /**
     * サインアップ.
     * @param $user_id SQEX user ID
     * @param $name プレイヤー名
     * @param $gender 性別
     * @param $devicePlatform 端末の種類
     * @return int 作成したプレイヤーのID
     */
    public function signup($user_id, $name, $gender, $devicePlatform)
    {
        $playersIdentity = $this->registerByUuid($user_id, $name, $gender, $devicePlatform);

        // 初期設定
        $this->setUpPlayer($playersIdentity->id, $devicePlatform);

        // // 招待報酬振込.
        // if (!empty($invitationId)) {
        //     $invitationService = new InvitationService();
        //     $invitationService->applyInvitationReward($playersIdentity->id, $invitationId);
        // }

        return $playersIdentity;
    }

    /**
     * UUIDで PlayersIdentity を登録.
     * @param $user_id SQEX user ID
     * @param $name 名
     * @param $gender 性別
     * @param string $devicePlatform     端末の種類
     */
    protected function registerByUuid($user_id, $name, $gender, $devicePlatform)
    {
        $app = App::getInstance();
        // user_idが空文字列であればエラー.
        if (strlen($user_id) == 0) {
            $app->responseArray = ["resultCode" => ResultCode::INSUFFICIENT_PARAMETERS];
            $app->halt(200);
        }
        // 既に PlayersIdentity が存在すればエラー.
        $playersIdentity = PlayersIdentity::findByUserId($user_id);
        if ($playersIdentity) {
            $app->logger->addDebug(var_dump($playersIdentity));
            $app->responseArray = ["resultCode" => ResultCode::PLAYER_ALREADY_EXISTS];
            $app->halt(200);
        }
        // PlayersIdentity を新規保存.
        $playersIdentity = ClusterORM::for_table('players_identities')->create(
            [
                "user_id" => $user_id,
                "invitation_id" => $this->generateInvitationId(),
                "name" => $name,
                "gender" => $gender,
                "device_platform" => $devicePlatform
            ]
        )->set_expr('created_at', 'NOW()');
        $playersIdentity->save();

        return $playersIdentity;
    }

    /**
     * Player と関連データ (同一シャードに保存するデータ) をトランザクション内で新規保存.
     * @param int $playerId       プレイヤーID
     * @param int $devicePlatform 端末の種類
     */
    protected function setUpPlayer($playerId, $devicePlatform)
    {
        // Player を新規保存. 同一シャードに保存するデータをトランザクション内で保存.
        $db = ClusterORM::for_table('players')
            ->select_shard($playerId)
            ->current_db();
        try {
            $db->beginTransaction();

            // 空のプレイヤー.
            $player = ClusterORM::for_table('players')
                ->create(
                    [
                        'id' => $playerId,
                        'friend_point' => 0,
                        'device_platform' => $devicePlatform
                    ]
                )->set_expr('created_at', 'NOW()');
            $player->save();
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
                $app->halt(200);
            }
        }
    }

    /**
     * 招待コードを生成する.
     * @return string
     */
    protected function generateInvitationId()
    {
        while (true) {
            $invitationId = sprintf("%09d", mt_rand(0, 999999999));
            // すでに存在するかチェック.
            $playersIdentity = ClusterORM::for_table('players_identities')
                ->select("id")
                ->where_equal('invitation_id', $invitationId)
                ->find_one();
            if (empty($playersIdentity)) {
                break;
            }
        }

        return $invitationId;
    }
}
