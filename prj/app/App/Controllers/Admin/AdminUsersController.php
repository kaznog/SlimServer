<?php
namespace App\Controllers\Admin;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\Models\Pager;
use \App\Services\AdminUserService;
use \App\App;

/**
 * 管理画面: admin_users 編集.
 */
class AdminUsersController
{
    const LIMIT = 50;

    /**
     * GET 管理ユーザ一覧画面
     */
    public static function index(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $page = $request->getParam("page");
        $page = $page ? (int) $page : 0;
        $limit = self::LIMIT;
        $offset = self::LIMIT * $page;
        $users = AdminUserService::getAdminUsers();
        $count = count($users);
        $rolesComments = AdminUserService::getRolesComments();
        
        $flash = $container->flash->getMessages();
        $response = $container->view->render(
            $response,
            "admin/admin_users/index.php",
            [
                "app" => $app,
                "pager" => new Pager("/admin/admin_users", $limit, $count, $page),
                "count" => $count,
                "users" => $users,
                "comments" => $rolesComments,
                "flash" => $flash,
            ]
        );
        return $response;
    }
    
    /**
     * GET 管理ユーザ一変更画面
     */
    public static function get(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $user_id = $args['user_id'];
        $my_user = AdminUserService::getMyAdminUser();
        $user = AdminUserService::getAdminUser($user_id);
        
        // 自分より格上を編集しようとした場合一覧にリダイレクト.
        if ($user['role'] < $my_user['role']) {
            $app->logService->logger('admin')->addDebug("my role[" . $my_user['role'] . "] user role[" . $user['role'] . "] redirect.");
            $response = $response->withStatus(302)->withHeader('Location', "/admin/admin_users");
            return $response;
        }
        
        $response = $container->view->render(
            $response,
            "admin/admin_users/get.php",
            [
                "app" => $app,
                "user_id" => $user_id,
                "role" => $user['role'],
                "roles" => AdminUserService::getRoles(),
            ]
        );
        return $response;
    }
    
    /**
     * POST 管理ユーザ一変更処理
     */
    public static function post(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $params = $request->getParams();
        $error_msg = "";
        
        if ($params['action'] == 'delete') {
            // 削除処理.
            $error_msg = AdminUserService::deleteAdminUser($params['uid']);
        }
        else {
            $error_msg = AdminUserService::updateAdminUser($params['uid'], null, $params['role']);
        }
        
        if ($error_msg != "") {
            // 作成できない場合エラー.
            $user = AdminUserService::getAdminUser($params['uid']);
            $container->flash->addMessageNow('error', $error_msg);
            $flash = $container->flash->getMessages();
            $response = $container->view->render(
                $response,
                "admin/admin_users/get.php",
                [
                    "app" => $app,
                    "user_id" => $user['user_id'],
                    "pass1" => $user['password'],
                    "pass2" => "",
                    "role" => $user['role'],
                    "roles" => AdminUserService::getRoles(),
                    "flash" => $flash,
                ]
            );
            return $response;
        }
        
        // ユーザーが追加されたので、super_adminへJSONを送付する
        AdminUserService::sendAdminUsers();

        $container->flash->addMessage('success', '設定変更に成功しました。');
        $response = $response->withStatus(302)->withHeader('Location', "/admin/admin_users");
        return $response;
    }
    
    /**
     * GET パスワード変更画面
     */
    public static function getPass(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $user = AdminUserService::getMyAdminUser();
        $pass = "";
        
        $response = $container->view->render(
            $response,
            "admin/admin_users/get_pass.php",
            [
                "app" => $app,
                "user_id" => $user['user_id'],
                "pass_now" => $pass,
                "pass1" => $pass,
                "pass2" => $pass,
            ]
        );
        return $response;
    }
    
    /**
     * POST パスワード変更処理
     */
    public static function postPass(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $params = $request->getParams();
        $error_msg = "";
        
        if (!isset($params['pass_now'])) {
            $error_msg = $error_msg . "現在のパスワードを入力してください。<br>";
        }
        else {
            if (!AdminUserService::checkPassword($params['user_id'], $params['pass_now'])) {
                $error_msg = $error_msg . "現在のパスワードが間違っています。<br>";
            }
        }
        
        if ($params['pass1'] != $params['pass2']) {
            $error_msg = $error_msg . "パスワードが異なっています。<br>";
        }
        
        if ($error_msg == "") {
            $error_msg = AdminUserService::updateAdminUser($params['user_id'], $params['pass1'], null);
        }
        
        if ($error_msg != "") {
            // 作成できない場合エラー.
            $container->flash->addMessageNow('error', $error_msg);
            $flash = $container->flash->getMessages();
            $response = $container->view->render(
                $response,
                "admin/admin_users/get_pass.php",
                [
                    "app" => $app,
                    "user_id" => $params['user_id'],
                    "pass_now" => "",
                    "pass1" => "",
                    "pass2" => "",
                    "flash" => $flash,
                ]
            );
            return $response;
        }
        
        // ユーザーが追加されたので、super_adminへJSONを送付する
        AdminUserService::sendAdminUsers();

        $container->flash->addMessage('success', 'パスワード変更に成功しました。');
        $response = $response->withStatus(302)->withHeader('Location', "/admin/index.php");
        return $response;
    }

}
