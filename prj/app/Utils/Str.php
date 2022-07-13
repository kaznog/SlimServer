<?php
namespace Utils;

/**
 * 文字列ユーティリティクラス.
 */
class Str
{
    /**
     * 文字列をCamelCaseに変換.
     * @param string  $str
     * @param boolean $ucfirst 文字列の最初を大文字にするなら TRUE.
     */
    public static function camelize($str, $ucfirst = TRUE)
    {
        $elements = explode('_', $str);
        $capitalized = array();
        if (!$ucfirst) {
            $capitalized[] = array_shift($elements);
        }
        while (!empty($elements)) {
            $capitalized[] = ucfirst(array_shift($elements));
        }

        return implode('', $capitalized);
    }

    /**
     * 時刻文字列を、タイムスタンプ（秒）に変換.
     *
     * @param  string $str DB時刻文字列
     * @return int    Unix timestamp
     */
    public static function strToTime($str)
    {
        return strtotime($str);
    }

    /**
     * Unixタイムスタンプを時刻文字列に変換.
     *
     * @param  int             $time Unix timestamp
     * @return 時刻文字列
     */
    public static function timeToStr($time)
    {
        return strftime('%Y-%m-%d %H:%M:%S', $time);
    }

    /**
     * Unixタイムスタンプを日付文字列に変換.
     *
     * @param  int             $time Unix timestamp
     * @return 日付文字列
     */
    public static function timeToDateStr($time)
    {
        return strftime('%Y-%m-%d', $time);
    }

    /**
     * 時刻を文字列に変換する.
     * @see http://www.php.net/manual/ja/datetime.format.php
     * @param  mixed  $datetime
     * @param  string $format
     * @return string $datetime が null であれば null.
     */
    public static function formatTime($time, $format = "Y-m-d\TH:i:s")
    {
        if (isset($time)) {
            if ($time instanceof \DateTime) {
                $datetime = $time;
            } elseif (is_string($time) && strlen($time) > 0) {
                $datetime = new \DateTime($time);
            } elseif (is_int($time)) {
                $datetime = new \DateTime();
                $datetime->setTimestamp($time);
            }
        }

        return isset($datetime) ? $datetime->format($format) : null;
    }
}
