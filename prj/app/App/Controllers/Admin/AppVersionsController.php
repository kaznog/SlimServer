<?php
namespace App\Controllers\Admin;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\Models\ClusterORM;
use \App\Models\AppVersion;
use \App\Models\Platform;
use \App\Services\ValidationService;
use \App\App;

/**
 * 管理画面: app_versions 編集.
 */
class AppVersionsController
{
    /**
     * GET /admin/app_versions
     */
    public static function index(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $entities = [];
        $entities[1] = AppVersion::get(Platform::PLATFORM_IOS);
        $entities[2] = AppVersion::get(Platform::PLATFORM_ANDROID);
        $response = $container->view->render(
            $response,
            "admin/app_versions/index.php",
            [
                "app" => $app,
                "entities" => $entities,
            ]
        );
        return $response;
    }

    /**
     * GET /admin/app_versions/{id}/edit
     */
    public static function edit(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        if (!in_array($args['id'], [Platform::PLATFORM_IOS, Platform::PLATFORM_ANDROID])) {
            $response = $response->withStatus(302)->withHeader('Location', '/admin/app_versions');
            return $response;
        }
        AppVersion::get($args['id']);
        $entity = ClusterORM::for_table('app_versions')->find_one($args['id']);
        $params = [
            "required_version" => $entity->required_version,
            "applying_version" => $entity->applying_version,
            "abdb_version"     => $entity->abdb_version,
        ];

        $flash = $container->flash->getMessages();
        $response = $container->view->render(
            $response,
            "admin/app_versions/edit.php",
            [
                "app" => $app,
                "id" => $args['id'],
                "entity" => $entity,
                "params" => $params,
                "flash" => $flash,
            ]
        );
        return $response;
    }

    /**
     * PUT /admin/app_versions/1
     */
    public static function put(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $params = $request->getParams();
        if (!in_array($args['id'], [Platform::PLATFORM_IOS, Platform::PLATFORM_ANDROID])) {
            $response = $response->withStatus(302)->withHeader('Location', '/admin/app_versions');
            return $response;
        }
        $errorMap = self::validateParams($params);

        AppVersion::get($args['id']);
        $entity = ClusterORM::for_table('app_versions')->find_one($args['id']);
        if (!empty($errorMap)) {
            $container->flash->addMessagehNow('errors', $errorMap);
            $flash = $container->flash->getMessages();
            $response = $container->view->render(
                $response,
                "admin/app_versions/edit.php",
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
        $response = $response->withStatus(302)->withHeader('Location', '/admin/app_versions');
        return $response;
    }

    /**
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
                "required_version":     {"type":["string"], "format":"regex", "pattern": "[0-9.]+"},
                "applying_version":     {"type":["string"], "format":"regex", "pattern": "[0-9.]*"},
                "abdb_version":         {"type":["string"], "format":"regex", "pattern": "[0-9]+"}
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
        $entity->required_version = $params['required_version'];
        $entity->applying_version = !empty($params['applying_version']) ? $params['applying_version'] : null;
        $entity->abdb_version = $params['abdb_version'];
        $entity->save();
        // キャッシュをクリア.
        AppVersion::clearCache();

        return $entity;
    }
}
