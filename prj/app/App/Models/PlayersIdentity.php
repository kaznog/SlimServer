<?php
namespace App\Models;

/**
 * プレイヤー識別子.
 */
class PlayersIdentity
{
    // 種別: UUID.
    const KIND_UUID = 1;

    // 性別: 男性.
    const GENDER_MALE = 1;
    // 性別: 女性.
    const GENDER_FEMALE = 2;

    // BANフラグ
    const STATUS_FLAG_BAN = 1;
    // 初期チュートリアル突破
    const STATUS_FLAG_TUTORIAL_CLEAR = 2;

    /**
     * user_id で PlayersIdentity を検索.
     * @param  string $user_id SQEX user ID
     * @return PlayersIdentity
     */
    public static function findByUserId($user_id)
    {
        $playersIdentity = ClusterORM::for_table('players_identities')
            ->where('user_id', $user_id)
            ->find_one();

        return $playersIdentity;
    }

    public static function findByPlayerId($playerId)
    {
        $playersIdentity = ClusterORM::for_table('players_identities')
            ->find_one($playerId);

        return $playersIdentity;
    }

    /**
     * プッシュ通知用情報変更.
     * @param $playerId プレイヤーID
     * @param $pushRegistrationId プッシュ通知ID
     */
    public static function setPushRegistrationId($playerId, $pushRegistrationId)
    {
        $playersIdentity = ClusterORM::for_table('players_identities')->find_one($playerId);
        $playersIdentity->push_registration_id = $pushRegistrationId;
        $playersIdentity->save();
    }

}
