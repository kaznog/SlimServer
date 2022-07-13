<?php
namespace App\Models;

/**
 * お知らせメッセージ
 */
class InfoMessage
{
    public static function get($kind = 0)
    {
        $msg = ClusterORM::for_table('info_messages')
                ->where_equal('kind', $kind)
                ->where_raw('start_at <= NOW()')
                ->where_raw('end_at > NOW()')
                ->find_one();
        if (isset($msg->message)) {
            return $msg->message;
        }

        return '';
    }

}
