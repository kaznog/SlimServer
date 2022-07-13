<?php
namespace App\Controllers\Admin;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \Utils\StringEncrypt;
use \App\Services\AdminUserService;

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
class LoginController {
    /*
     * ログイン画面
     */
    public static function get(Request $request, Response $response, Array $args)
    {
        // スーパーユーザが作成されていなければ作成する.
        if (!AdminUserService::existsAdminUser("super_admin")) {
            AdminUserService::updateAdminUser("super_admin", "hichewL0chew", 0);
        }
        
        $app = \App\App::getInstance();
        $app->logService->logger('admin')->addDebug("LoginController::get");
        // $app->logService->logger('admin')->addDebug(var_export($request, true));
        // $app->logService->logger('admin')->addDebug(var_export($response, true));
        $user_id = "";
        return $app->getContainer()->view->render(
            $response,
            "admin/login/get.php",
            [
                "app" => $app,
                "user_id" => $user_id
            ]
        );
    }
    
    /*
     * ログイン処理
     */
    public static function post(Request $request, Response $response, Array $args)
    {
        $app = \App\App::getInstance();
        $app->logService->logger('admin')->addDebug("LoginController::post");
        $container = $app->getContainer();
        $params = $request->getParsedBody();
        if(AdminUserService::checkPassword($params['uid'], $params['pass'])) {
            $app->logService->logger('admin')->addDebug("LoginController::post password checked");
            $crypted_password = AdminUserService::encodePassword($params['pass']);
            $container['cookie']->set('uid', ['value' => $params['uid'], 'expires' => '60 minutes']);
            $container['cookie']->set('key', ['value' => $crypted_password, 'expires' => '60 minutes']);
            $response = $response->withStatus(302)->withHeader('Location', '/admin/index.php');
        } else {
            $app->logService->logger('admin')->addDebug("LoginController::post password check faild.");
            $container->flash->addMessageNow('error', "ログインに失敗しました。");
            $flash = $container->flash->getMessages();
            $response = $container->view->render(
                $response,
                "admin/login/get.php",
                [
                    "app" => $app,
                    "user_id" => $params['uid'],
                    "flash" => $flash,
                ]
            );
        }
        return $response;
    }
}
