<?php
namespace App\Controllers\Admin;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\Models\ClusterORM;
use \App\Models\Maintenance;
use \App\Services\ValidationService;
use \Utils\Str;
use \App\App;

/**
 * 管理画面: maintenances 編集.
 */
class MaintenancesController
{
    /**
     * GET /admin/maintenances/1
     */
    public static function get(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $app->logService->logger('admin')->addDebug(__CLASS__ . '::' . __FUNCTION__ . ' args: ' . var_export($args, true));
        $flash = $container->flash->getMessages();
        $entity = Maintenance::get();
        return $container->view->render(
            $response,
            "admin/maintenances/get.php",
            [
                "app" => $app,
                "entity" => $entity,
                "flash" => $flash,
            ]
        );
    }

    /**
     * GET /admin/maintenances/1/edit
     */
    public static function edit(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $flash = $container->flash->getMessages();
        $entity = ClusterORM::for_table('maintenances')->find_one(Maintenance::ID);
        $params = [
            "start_at" => Str::formatTime($entity->start_at, 'Y-m-d\TH:i:s'),
            "end_at" => Str::formatTime($entity->end_at, 'Y-m-d\TH:i:s'),
            "message" => $entity->message,
        ];

        return $container->view->render(
            $response,
            "admin/maintenances/edit.php",
            [
                "app" => $app,
                "entity" => $entity,
                "params" => $params,
                "flash" => $flash,
            ]
        );
    }

    /**
     * PUT /admin/maintenances/1
     */
    public static function put(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $params = $request->getParams();
        $params['start_at:formatted'] = Str::formatTime($params['start_at'], 'Y-m-d\TH:i:sP');
        $params['end_at:formatted'] = Str::formatTime($params['end_at'], 'Y-m-d\TH:i:sP');
        $errorMap = self::validateParams($params);

        $entity = ClusterORM::for_table('maintenances')->find_one(Maintenance::ID);
        if (!empty($errorMap)) {
            $container->flash->addMessageNow('errors', $errorMap);
            $flash = $container->flash->getMessages();
            $response = $container->view->render(
                $response,
                "admin/maintenances/edit.php",
                [
                    "app" => $app,
                    "entity" => $entity,
                    "params" => $params,
                    "flash" => $flash,
                ]
            );

            return $response;
        }
        // 保存
        self::saveEntity($entity, $params);

        $container->flash->addMessage('success', "更新しました.");
        $response = $response->withStatus(302)->withHeader('Location', "/admin/maintenances");
        return $response;
    }

    /**
     * メンテナンスを即座に開始.
     * PUT /admin/maintenances/1/start
     */
    public static function start(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $now = new \Datetime();
        $entity = ClusterORM::for_table('maintenances')->find_one(Maintenance::ID);
        $entity->start_at = $now->format('Y-m-d H:i:s');
        $entity->end_at = null;
        $entity->save();
        // キャッシュをクリア.
        Maintenance::clearCache();

        $container->flash->addMessage('success', "メンテナンス開始しました.");
        $response = $response->withStatus(302)->withHeader('Location', "/admin/maintenances");
        return $response;
    }

    /**
     * DELETE /admin/maintenances/1
     */
    public static function delete(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $entity = ClusterORM::for_table('maintenances')->find_one(Maintenance::ID);
        if ($entity) {
            $entity->delete();
        }
        // キャッシュをクリア.
        Maintenance::clearCache();

        $response = $response->withStatus(302)->withHeader('Location', "/admin/maintenances");
        return $response;
    }

    /**
     * @param  int   $id
     * @param  array $params
     * @return array エラー配列
     */
    protected static function validateParams($params)
    {
        // validation
        $schema = '
        {
            "type":"object",
            "properties":{
                "start_at:formatted":   {"type":["string", "null"], "format":"date-time"},
                "end_at:formatted":     {"type":["string", "null"], "format":"date-time"},
                "message":              {"type":"string"}
            }
        }';

        $errors = ValidationService::validateJsonForAdmin((object) $params, $schema);
        $errorMap = [];
        foreach ($errors as $error) {
            $errorMap[$error['property']] = $error['message'];
        }

        return $errorMap;
    }

    /**
     * メンテナンス情報を保存して返す.
     * @param  ClusterORM $entity
     * @param  array      $paramas
     * @return ClusterORM
     */
    protected static function saveEntity($entity, $params)
    {
        $entity->start_at = Str::formatTime($params["start_at"]);
        $entity->end_at = Str::formatTime($params["end_at"]);
        $entity->message = $params["message"];
        $entity->save();
        // キャッシュをクリア.
        Maintenance::clearCache();

        return $entity;
    }
}
