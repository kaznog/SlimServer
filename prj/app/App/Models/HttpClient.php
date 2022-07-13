<?php
namespace App\Models;

use \App\App;

/**
 * cURL のラッパー.
 * cURL については、http://php.net/manual/ja/book.curl.php
 */
class HttpClient
{
    /**
     * GET リクエストを実行.
     * @param  string $url     URL
     * @param  int    $timeout タイムアウト時間(秒)
     * @param  array  $opts    curl_setopt() にセットする配列.
     * @return mixed  レスポンス.
     */
    public function get($url, $timeout = 0, $opts = [])
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        foreach ($opts as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
        $response = $this->sendRequest($ch);

        return $response;
    }

    /**
     * POST リクエストを実行.
     * @param  string $url        URL
     * @param  mixed  $postFields POSTデータ.
     * @param  int    $timeout    タイムアウト時間(秒)
     * @param  array  $opts       curl_setopt() にセットする配列.
     * @return mixed  レスポンス.
     * @return mixed  レスポンス.
     */
    public function post($url, $postFields, $timeout = 0, $opts = [])
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        foreach ($opts as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
        $response = $this->sendRequest($ch);

        return $response;
    }

    /**
     * セッションを実行して、エラーが発生すれば処理する.
     * @param  resource $ch       cURLハンドル.
     * @param  resource $this->ch cURLハンドル.
     * @return mixed    レスポンス.
     */
    protected function sendRequest($ch)
    {
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $errmsg = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $app = App::getInstance();

        // リクエストが成功しなければエラー終了.
        if ($errno != 0) {
            $app->logger->addNotice("cURL request failed: {$errno} {$errmsg}");
            $app->logger->addNotice(var_export($info, true));
            if ($errno === CURLE_OPERATION_TIMEOUTED) {
                $app->responseArray = ["resultCode" => ResultCode::CURL_CONNECTION_TIMEOUT];
            } else {
                $app->responseArray = ["resultCode" => ResultCode::CURL_CONNETION_ERROR];
            }
            $app->halt(500);
        }

        return $response;
    }
}

?>