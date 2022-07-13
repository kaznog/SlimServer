<?php
namespace Utils;

/**
 * IDにスクランブルをかける
 */
class ScrambleID
{

    /*
     * スクランブルをかける
     * @param $id かける値
     */
    public static function scramble($id)
    {
        $value = $id;
        $split = array();
        for ($i = 0; $i < 12; $i++) {
            $split[] = $value & 0x3;
            $value >>= 2;
        }

        $code = 0;
        for ($i = 0; $i < 12; $i++) {
            $code <<= 2;
            $code += $split[$i];
        }
        $scrambled = $code ^ 105000000;

        return $scrambled;
    }
    /*
     * スクランブルを戻す
     * @param $scrambled スクランブルがかかった値
     */
    public static function desucramble($scrambled)
    {
        $value = $scrambled ^ 105000000;
        $split = array();
        for ($i = 0; $i < 12; $i++) {
            $split[] = $value & 0x3;
            $value >>= 2;
        }
        $code = 0;
        for ($i = 0; $i < 12; $i++) {
            $code <<= 2;
            $code += $split[$i];
        }

        return $code;
    }

}
