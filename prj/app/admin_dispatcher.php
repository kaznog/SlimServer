<?php
/**
 * 管理サイト(/admin以下)のディスパッチ設定.
 */
use \App\Models\ResultCode;
use \Slim\Http\Request;
use \Slim\Http\Response;

// session
session_cache_limiter(false);
session_start();

$authenticate = function (Request $request, Response $response, callable $next) {
    $app = \App\App::getInstance();
    $container = $app->getContainer();
    $app->logService->logger('admin')->addDebug('authenticate');
    $users = \App\Services\AdminUserService::getAdminUsers();
    $roles = \App\Services\AdminUserService::getRoles();

    $uid = $container['cookie']->get('uid');
    $key = $container['cookie']->get('key');
    $user_setting = null;
    if (empty($uid) || empty($key)) {
        // ログインセッションがない場合はログインページへ遷移
	    $app->logService->logger('admin')->addDebug('non session value redirect /admin/login');
        return $response->withStatus(302)->withHeader('Location', '/admin/login');
    } else {
	    $app->logService->logger('admin')->addDebug('validate');
        $app->logService->logger('admin')->addDebug('uid:' . $uid);
        $app->logService->logger('admin')->addDebug('key:' . $key);
        $app->logService->logger('admin')->addDebug('users:' . var_export($users, true));
        if (validateUserKey($uid, $key, $users, $user_setting)) {
		    $app->logService->logger('admin')->addDebug('validated !');
            $container['cookie']->set('uid', ['value' => $uid, 'expires' => '60 minutes']);
            $container['cookie']->set('key', ['value' => $key, 'expires' => '60 minutes']);

			$route_path = $request->getAttribute('route')->getPattern();
			$app->logService->logger('admin')->addDebug("admin_dispatcher authenticate route path {$route_path}");
			$method = $request->getMethod();
			$app->logService->logger('admin')->addDebug("admin_dispatcher authenticate route method {$method}");
            if (!isset($roles[$user_setting['role']])) {
                // $route_pathがpagesにない場合は「権限がありません」ページへ遷移させる
                $this->flash->addMessage('error', 'アクセス権限がありません。<br>');
                return $response->withStatus(302)->withHeader('Location', '/admin/error');
            } else {
                $pages = $roles[$user_setting['role']]['pages'];
                foreach($pages as $ignore_page) {
                    if(isset($ignore_page['pattern'])
                    && $ignore_page['pattern'] == $route_path && $ignore_page['method'] == $method) {
                        // 使用不可リクエストパターンとして登録されていた場合、
                        // HTTPメソッドも合致したら使用不可ページとして処理する
                        $this->flash->addMessage('error', 'アクセス権限がありません。<br>');
                        return $response->withStatus(302)->withHeader('Location', '/admin/error');
                    }
                }
            }
            // 認証成功、権限も問題なし //
            \App\Services\AdminUserService::addLog($request);
        } else {
            // ログインセッションが不正な場合は再ログイン
		    $app->logService->logger('admin')->addDebug('redirect /admin/login');
	        $response->withStatus(302)->withHeader('Location', '/admin/login');
	        return $response;
        }
	}
	$response = $next($request, $response);
	return $response;
};

function validateUserKey($uid, $key, $admin_users, &$user_setting) {
    $result = false;
    foreach($admin_users as $idx => $user) {
        if($user['user_id'] == $uid) {
            $user_setting = $user;

            if (\App\Services\AdminUserService::decodePassword($user['password']) == \App\Services\AdminUserService::decodePassword($key)) {
                $result = true;
            }
            break;
        }
    }
    return $result;
}

// 認証なしURLにて使用.
$not_authenticate = function (Request $request, Response $response, callable $next) {
	\App\Services\AdminUserService::addLog($request);
	$response = $next($request, $response);
	return $response;
};

$container = $app->getContainer();
$container['flash'] = function() {
	return new \Slim\Flash\Messages();
};
$container['view'] = function() use ($container) {
	return new \Slim\Views\PhpRenderer($container['settings']['templates.path']);
};
$app->add( new \Middlewares\UseCookie() );
$app->add( new \Middlewares\UseMonolog() );

// routing
$app->get('/login', ['\App\Controllers\Admin\LoginController', 'get'])->add($not_authenticate);

$app->post('/login', ['\App\Controllers\Admin\LoginController', 'post'])->add($not_authenticate);

$app->get('/', function (Request $request, Response $response, Array $args) use ($app) {
    $container = $app->getContainer();
    $app->logService->logger('admin')->addDebug('index');
    $app->logService->logger('admin')->addDebug("pattern: " . $request->getAttribute('route')->getPattern() );
    $app->logService->logger('admin')->addDebug("method:  " . $request->getMethod());
    $is_callable = is_callable(['\App\Controllers\Admin\LoginController', 'get']) ? "true" : "false";
    $app->logService->logger('admin')->addDebug("LoginController::get is_callable: " . $is_callable);
    $flash = $this->flash->getMessages();
    return $this->view->render($response, 'admin/index.php', ['app' => $app, 'flash' => $flash]);
})->add($authenticate);

$app->get('/error', function (Request $request, Response $response, Array $args) use ($app) {
    $flash = $this->flash->getMessages();
    return $this->view->render($response, 'admin/error.php', ['app' => $app, 'flash' => $flash]);
})->add($not_authenticate);

//--------------------------------------------------
// 招待
//--------------------------------------------------
$app->group('/invitation', function () {
    $this->map(['GET'], '', ['\App\Controllers\Admin\InvitationController', 'get'] );
    $this->map(['POST'], '', ['\App\Controllers\Admin\InvitationController', 'post'] );
})->add($authenticate);

//--------------------------------------------------
// ユーザ登録
//--------------------------------------------------
$app->group('/signup', function () {
    $this->map(['GET'], '', ['\App\Controllers\Admin\SignupController', 'get'] );
    $this->map(['POST'], '', ['\App\Controllers\Admin\SignupController', 'post'] );
})->add($not_authenticate);

//--------------------------------------------------
// 管理ユーザ
//--------------------------------------------------
$app->get('/admin_users', ['\App\Controllers\Admin\AdminUsersController', 'index'] )->add($authenticate);
$app->group('/admin_users/{user_id}', function () {
    $this->map(['GET'], '', ['\App\Controllers\Admin\AdminUsersController', 'get']);
    $this->map(['POST'], '', ['\App\Controllers\Admin\AdminUsersController', 'post']);
})->add($authenticate);

$app->group('/password', function () {
    $this->map(['GET'], '', ['\App\Controllers\Admin\AdminUsersController', 'getPass'] );
    $this->map(['POST'], '', ['\App\Controllers\Admin\AdminUsersController', 'postPass'] );
})->add($authenticate);

//--------------------------------------------------
// 権限レベル
//--------------------------------------------------
$app->group('/roles', function () {
    $this->map(['GET'], '', ['\App\Controllers\Admin\AdminRolesController', 'index']);
    $this->map(['POST'], '', ['\App\Controllers\Admin\AdminRolesController', 'update']);
})->add($authenticate);
$app->get('/roles/{id:[0-9]+}', ['\App\Controllers\Admin\AdminRolesController', 'get'])->add($authenticate);

//--------------------------------------------------
// ログ参照
//--------------------------------------------------
$app->get('/logs', ['\App\Controllers\Admin\AdminLogsController', 'index'])->add($authenticate);

$app->get('/logs/get_csv', ['\App\Controllers\Admin\AdminLogsController', 'getCsv'])->add($authenticate);

//--------------------------------------------------
// コンソール
//--------------------------------------------------
$app->get(
    '/console/top',
    function (Request $request, Response $response, Array $args) use ($app) {
        if ($app->mode === "production") {
            $app->halt(403);
        }
        return $this->view->render($response, "admin/console/top.php", ["app" => $app]);
    }
)->add($authenticate);
//--------------------------------------------------
// メンテナンス
//--------------------------------------------------
$app->get('/maintenances', ['\App\Controllers\Admin\MaintenancesController', 'get'] )->add($authenticate);
$app->group('/maintenances/{id:[0-9]+}', function () {
    $this->map(['PUT'], '', ['\App\Controllers\Admin\MaintenancesController', 'put'] );
    $this->map(['DELETE'], '', ['\App\Controllers\Admin\MaintenancesController', 'delete'] );
    $this->get('/start', ['\App\Controllers\Admin\MaintenancesController', 'start'] );
    $this->get('/edit', ['\App\Controllers\Admin\MaintenancesController', 'edit'] );
})->add($authenticate);

//--------------------------------------------------
// アプリバージョン
//--------------------------------------------------
$app->get('/app_versions', ['\App\Controllers\Admin\AppVersionsController', 'index'] )->add($authenticate);
$app->group('/app_versions/{id:[0-9]+}', function() {
    $this->map(['PUT'], '', ['\App\Controllers\Admin\AppVersionsController', 'put'] );
    $this->get('/edit', ['\App\Controllers\Admin\AppVersionsController', 'edit'] );
})->add($authenticate);

//--------------------------------------------------
// お知らせ
//--------------------------------------------------
$app->get('/notices', ['\App\Controllers\Admin\NoticesController', 'index'])->add($authenticate);
$app->post('/notices', ['\App\Controllers\Admin\NoticesController', 'post'])->add($authenticate);
$app->get('/notices/new', ['\App\Controllers\Admin\NoticesController', 'create'])->add($authenticate);
$app->group('/notices/{id:[0-9]+}', function () {
    $this->map(['GET'], '', ['\App\Controllers\Admin\NoticesController', 'get']);
    $this->map(['PUT'], '', ['\App\Controllers\Admin\NoticesController', 'put']);
    $this->get('/edit', ['\App\Controllers\Admin\NoticesController', 'edit']);
})->add($authenticate);

//--------------------------------------------------
// プレイヤー
//--------------------------------------------------
$app->get('/players', ['\App\Controllers\Admin\PlayersController', 'index'])->add($authenticate);
$app->group('/players/{id:[0-9]+}', function() {
    $this->map(['GET'], '', ['\App\Controllers\Admin\PlayersController', 'get']);
    $this->map(['PUT'], '', ['\App\Controllers\Admin\PlayersController', 'put']);
    $this->get('/edit', ['\App\Controllers\Admin\PlayersController', 'edit']);
})->add($authenticate);

?>