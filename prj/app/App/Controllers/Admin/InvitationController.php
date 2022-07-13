<?php
namespace App\Controllers\Admin;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \Utils\StringEncrypt;
use \Utils\Mail;
use \App\Services\AdminUserService;
use \App\App;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of InvitationController
 *
 * @author k-noguchi
 */
class InvitationController {

    /*
     * GET 招待画面
     */
    public static function get(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $user_id = "";
        $role = "0";
        $flash = $container->flash->getMessages();
        $app->logService->logger('admin')->addDebug('flash message: ' . var_export($flash, true));
        $response = $container->view->render(
            $response,
            "admin/invitation/get.php",
            [
                "app" => $app,
                "roles" => AdminUserService::getRoles(),
                "user_id" => $user_id,
                "role" => $role,
                "flash" => $flash,
            ]
        );
        return $response;
    }
    
    /*
     * POST 招待処理
     */
    public static function post(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $params = $request->getParams();
        
        $error_msg = "";
        
        if ($params['uid'] == "") {
            $error_msg = $error_msg."メールアドレスを入力してください。<br>";
        }
        
        if (strpos($params['uid'], '@') === false) {
            $error_msg = $error_msg."メールアドレスではありません。<br>";
        }
        
        if (AdminUserService::existsAdminUser($params['uid'])) {
            $error_msg = $error_msg."既にユーザが存在しています。<br>";
        }
        
        if ($error_msg != "") {
            // 作成できない場合エラー.
            $app->logService->logger('admin')->addDebug(__CLASS__ . '::' . __FUNCTION__ . ' error_msg: ' . $error_msg );
            $container->flash->addMessageNow('error', $error_msg);
            $flash = $container->flash->getMessages();
            $response = $container->view->render(
                $response,
                "admin/invitation/get.php",
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
        
        $hostname = $_SERVER["HTTP_HOST"];
        $http_str = empty($_SERVER["HTTPS"]) ? "http://" : "https://";
        $time_limit = date('Y-m-d H:i:s', strtotime('+8 hours'));
        $param_str = "{$params['uid']}&{$params['role']}&{$time_limit}";
        $param_str = self::encHash($param_str);
        $url = "{$http_str}{$hostname}/admin/signup?p={$param_str}";
        
        // リクエストハッシュを残す.
        AdminUserService::addLog(null, $param_str);
        
        mb_internal_encoding("UTF-8");
        $to = $params['uid'];
        $subject = "「App Admin Tool」 への招待";
        $message = '<html><body bgcolor="#aabbff">';
        $message .= "{$params['uid']} 様<br>";
        $message .= " <br>";
        $message .= "App Admin Tool [{$app->mode}] へ招待されました。<br>";
        $message .= " <br>";
        $message .= "以下のリンクへアクセスし登録を完了させてください。<br>";
        $message .= "<a href={$url}>{$url}</a><br>";
        $message .= "※こちらのURLは8時間有効です。<br>";
        $message .= " <br>";
        $message .= "このメールは自動送信メールです。このメールに返信されましてもアプリ開発者へは届きませんのでご了承ください。<br>";
        $message .= " <br>";
        $message .= "</body></html>";
        Mail::sendGmail('apptool.information@gmail.com', 'hichewL0chew', $to, $subject, $message, "", "App Admin Tool");
        
        $container->flash->addMessage('success', "仮登録メールを送信しました。");
        $response = $response->withStatus(302)->withHeader('Location', '/admin/invitation');
        return $response;
    }
    
    /*
     * ハッシュ化
     * @return string
     */
    protected static function encHash($str)
    {
        $str_encrypt = new StringEncrypt();
        $crypt_pw = 'hichewL0chew';
        $str = $str_encrypt->encrypt($str, $crypt_pw);
        //$str = mb_convert_encoding(rtrim($str), 'UTF-8');
        return $str;
    }
    
    
}
