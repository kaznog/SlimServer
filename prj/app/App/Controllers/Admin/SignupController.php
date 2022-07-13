<?php
namespace App\Controllers\Admin;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \Utils\StringEncrypt;
use \App\Services\AdminUserService;
use \App\App;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of LoginController
 *
 * @author k-noguchi
 */
class SignupController {

    /*
     * GET ユーザ登録画面
     */
    public static function get(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $param = $request->getParam('p');
        $param = (isset($param)) ? $param : "";
        
        if($param == "") {
            $container->flash->addMessage('error', 'URLが無効です。<br>');
            $response = $response->withStatus(302)->withHeader('Location', '/admin/error');
            return $response;
        }
        
        // POSTされたデータの[ ]を[+]に変換しないと正常に変換されない.
        $param = str_replace(' ', '+', $param);
        
        $param = self::decHash($param);
        $params = explode('&', $param);
        
        $uid  = $params[0];
        $role = $params[1];
        $time = $params[2];
        
        $current_time = date('Y-m-d H:i:s');
        if(strtotime($time) < strtotime($current_time)) {
            $container->flash->addMessage('error', '一定時間が経過したためURLが無効です。<br>');
            $response = $response->withStatus(302)->withHeader('Location', '/admin/error');
            return $response;
        }
        
        $response = $container->view->render(
            $response,
            "admin/signup/get.php",
            [
                "app" => $app,
                "roles" => AdminUserService::getRoles(),
                "user_id" => $uid,
                "role" => $role
            ]
        );
        return $response;
    }
    
    /*
     * ユーザ登録処理
     */
    public static function post(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $params = $request->getParams();
        $error_msg = "";
        if ($params['uid'] == "") {
            $error_msg = $error_msg."ユーザIDが入力されていません。<br>";
        }
        
        if ($params['pass'] == "" || $params['pass2'] == "") {
            $error_msg = $error_msg."パスワードが入力されていません。<br>";
        }
        
        // 作成済か確認.
        if (AdminUserService::existsAdminUser($params['uid'])) {
            $error_msg = $error_msg."既にユーザが存在しています。<br>";
        }
        
        // パスワード確認
        if ($params['pass'] !== $params['pass2']) {
            $error_msg = $error_msg."パスワードが異なっています。<br>";
        }
        
        if ($error_msg == "") {
            $error_msg = $error_msg . AdminUserService::updateAdminUser($params['uid'], $params['pass'], $params['role']);
        }
        
        if ($error_msg != "") {
            // 作成できない場合エラー.
            $container->flash->addMessageNow('error', $error_msg);
            $flash = $container->flash->getMessages();
            $response = $container->view->render(
                $response,
                "admin/signup/get.php",
                [
                    "app" => $app,
                    "roles" => AdminUserService::getRoles(),
                    "user_id" => $params['uid'],
                    "role" => $params['role'],
                    "flash" => $flash,
                ]
            );
            return $response;
        }
        // ユーザーが追加されたので、super_adminへJSONを送付する
        AdminUserService::sendAdminUsers();
        // 成功.
        $container->flash->addMessage('success', "アカウント作成に成功しました。");
        $response = $response->withStatus(302)->withHeader('Location', '/admin/index.php');
        return $response;
    }
    
    /*
     * hashをデコードする
     * @param string $str
     * @return string
     */
    protected static function decHash($str)
    {
        $str_encrypt = new StringEncrypt();
        $crypt_pw = 'hichewL0chew';
        $str = $str_encrypt->decrypt($str, $crypt_pw);
        $str = mb_convert_encoding(rtrim($str), 'UTF-8');
        return $str;
    }

}
