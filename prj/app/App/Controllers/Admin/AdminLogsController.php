<?php
namespace App\Controllers\Admin;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\Models\Pager;
use \App\Services\AdminUserService;
use \App\App;

/**
 * 管理画面: logs 編集.
 */
class AdminLogsController
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
        $app->logService->logger('admin')->addDebug("admin logs page:" . $page);

        $search = $request->getParam("search");
        $user_id = $request->getParam("user_id");
        $method = $request->getParam("method");
        $ptn = $request->getParam("ptn");
        $start_date = $request->getParam("start_date");
        $end_date = $request->getParam("end_date");

        $search  = isset($search) ? $search : "";
        $user_id = isset($user_id) ? $user_id : "";
        $method  = isset($method) ? $method : "";
        $ptn     = isset($ptn) ? $ptn : "-1";
        $start_date = isset($start_date) ? $start_date : "";
        $end_date = isset($end_date) ? $end_date : "";
        
        $limit = self::LIMIT;
        $offset = $limit * $page;
        
        // 一定以上の権限がない場合は、自身のみの検索とする.
        $my_user = AdminUserService::getMyAdminUser();
        if ($my_user['role'] > 1) {
            $user_id = $my_user['user_id'];
        }
        
        // WHERE 句を作成.
        $wheres = [];
        $wheres['user_id'] = ($user_id != "") ? " AND user = '{$user_id}' " : "";
        $wheres['method'] = ($method != "") ? " AND method = '{$method}' " : "";
        if ($ptn != "" && $ptn != "-1") {
            $patterns = AdminUserService::getPatterns();
            foreach ($patterns as $idx => $pat) {
                if ($idx == $ptn) {
                    $wheres['ptn'] = " AND pattern = '{$pat['pattern']}' ";
                    break;
                }
            }
        }
        if ($search != "") {
            $wheres['search'] = " AND (comments LIKE '%{$search}%' OR  pattern LIKE '%{$search}%') ";
        }
        if ($start_date != "") {
            $date_str = str_replace('T', ' ', $start_date);   // 文字化け回避.
            $wheres['start_date'] = " AND created_at >= '{$date_str}' ";
        }
        if ($end_date != "") {
            $date_str = str_replace('T', ' ', $end_date);   // 文字化け回避.
            $wheres['end_date'] = " AND created_at <= '{$date_str}' ";
        }
        
        // query作成.
        $where = "";
        foreach ($wheres as $w) {
            $where .= $w;
        }
        
        // query 発行
        $query = "SELECT * FROM logs WHERE 1 {$where} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}";
        $logs = AdminUserService::getLog($query);
        foreach ($logs as &$log) {
            $log['pattern_name'] = AdminUserService::getPattern($log['pattern'], $log['method']);            
        }
        
        // count query 発行
        $query = "SELECT COUNT(*) as 'count' FROM logs WHERE 1 {$where} ORDER BY id DESC";
        $count = AdminUserService::getLog($query)[0]['count'];
        $app->logService->logger('admin')->addDebug("admin logs count:" . $count);
        
        $response = $container->view->render(
            $response,
            "admin/logs/index.php",
            [
                "app" => $app,
                "pager" => new Pager("/admin/logs", $limit, $count, $page),
                "count" => $count,
                "logs" => $logs,
                "search" => $search,
                "user_id" => $user_id,
                "method" => $method,
                "ptn" => $ptn,
                "start_date" => $start_date,
                "end_date" => $end_date,
            ]
        );
        return $response;
    }
    
    /**
     * GET 管理ユーザ一覧 CSV取得
     */
    public static function getCsv(Request $request, Response $response, Array $args)
    {
        $filename = $request->getParam("filename");
        $path = $app->config('backup_uploaded.path');
        $text = file_get_contents($path . $filename);
        $response->withHeader('Content-Type', 'text/csv')->withHeader('Content-Disposition', "attachment; filename=\".{$filename}\"");
        $response->getBody()->write($text . "\r\n");
    }
    
}
