<?php
namespace App\Controllers\Admin;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\Models\Pager;
use \App\Services\AdminUserService;
use \App\App;

/**
 * 管理画面: roles 編集.
 */
class AdminRolesController
{
    const LIMIT = 100;
    
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
        $offset = $limit * $page;
        $count = 0;
        
        //$role = 0;
        $roles = AdminUserService::getRoles();
        
        $response = $container->view->render(
            $response,
            "admin/roles/index.php",
            [
                "app" => $app,
                "pager" => new Pager("/admin/roles", $limit, $count, $page),
                "count" => $count,
                "roles" => $roles,
            ]
        );
        return $response;
    }
    
    /**
     * GET 管理ユーザ詳細画面
     */
    public static function get(Request $request, Response $response, Array $args)
    {        
        $app = App::getInstance();
        $container = $app->getContainer();
        $id = $args['id'];
        $roles = AdminUserService::getRoles();
        $role = null;
        foreach ($roles as $idx => $tmp) {
            if ($idx == $id) {
                $role = $tmp;
            }
        }
        
        $response = $container->view->render(
            $response,
            "admin/roles/get.php",
            [
                "app" => $app,
                "role" => $role,
            ]
        );
        return $response;
    }
    
    /**
     * POST 権限更新.
     */
    public static function update(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $params = $request->getParams();        
        switch ($params['action']) {
            case "insert_role":             // 新規権限の追加.
                AdminUserService::updateRole($params['id'], $params['comments']);
                AdminUserService::sendAdminUsersRoles();
                $response = $response->withStatus(302)->withHeader('Location', '/admin/roles');
                break;
            case "update_comments":         // 権限のコメントの編集.
                AdminUserService::updateRole($params['id'], $params['comments']);
                AdminUserService::sendAdminUsersRoles();
                $response = $response->withStatus(302)->withHeader('Location', "/admin/roles/{$params['id']}");
                break;
            case "copy_ignore_pages":       // 既存の権限の無効ページ設定で上書き.
            {
                $srcId = $params['src'];
                if ($srcId != -1) {
                    $ids = [];
                    $patterns = AdminUserService::getPatterns();
                    $roles = AdminUserService::getRoles();
                    foreach ($roles as $role) {
                        // コピー元のpage_id を取得.
                        if ($role['id'] == $srcId) {
                            foreach ($role['pages'] as $page) {
                                foreach ($patterns as $pattern) {
                                    if ($page['method'] == $pattern['method'] && $page['pattern'] == $pattern['pattern']) {
                                        $ids[] = $pattern['id'];
                                    }
                                }
                            }
                        }
                    }
                    AdminUserService::updateIgnorePages($params['id'], $ids);
                    AdminUserService::sendAdminUsersRoles();
                }
                $response = $response->withStatus(302)->withHeader('Location', "/admin/roles/{$params['id']}");
                break;
            }
            case "update_ignore_pages":     // 無効ページ設定を更新.
            {
                $ids = [];
                $keys = array_keys($params);
                foreach ($keys as $key) {
                    if (strpos($key, "check_") !== false && $params[$key] == "on") {
                        $id = (int)str_replace("check_", "", $key);
                        $ids[] = $id;
                    }
                }
                AdminUserService::updateIgnorePages($params['id'], $ids);
                AdminUserService::sendAdminUsersRoles();
                $response = $response->withStatus(302)->withHeader('Location', "/admin/roles/{$params['id']}");
                break;
            }
            case "delete_role":             // 権限を削除
                $id = $params['id'];
                $roles = AdminUserService::getRoles();
                if ((count($roles) - 1) == $id) {
                    // 一番最後の権限のみ削除可能.
                    AdminUserService::deleteRole($id);
                    AdminUserService::sendAdminUsersRoles();
                }
                $response = $response->withStatus(302)->withHeader('Location', "/admin/roles");
                break;
            default:
                $response = $response->withStatus(302)->withHeader('Location', "/admin");
                break;
        }
        return $response;
    }
    
}
