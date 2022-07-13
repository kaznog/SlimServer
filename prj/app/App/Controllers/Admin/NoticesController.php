<?php
namespace App\Controllers\Admin;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\Models\ClusterORM;
use \App\Models\MasterORM;
use \App\Models\Pager;
use \App\Services\ValidationService;
use \Utils\Str;
use \App\App;

/**
 * 管理画面: notices 編集.
 */
class NoticesController
{
    const LIMIT = 50;

    /**
     * GET /admin/notices
     * @param \Slim\Slim $app
     */
    public static function index(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $page = isset($args['page']) ? (int) $args['page'] : 0;
        $limit = self::LIMIT;
        $offset = self::LIMIT * $page;
        $count = 0;

        $count = ClusterORM::for_table("notices")
            ->count();
        $entities = ClusterORM::for_table("notices")
            ->order_by_desc("id")
            ->offset($offset)
            ->limit($limit)
            ->find_many();
        $response = $container->view->render(
            $response,
            "admin/notices/index.php",
            [
                "app" => $app,
                "pager" => new Pager("/admin/notices", $limit, $count, $page),
                "count" => $count,
                "entities" => $entities
            ]
        );
        return $response;
    }

    /**
     * GET /admin/notices/new
     * @param \Slim\Slim $app
     */
    public static function create(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $params = [
            "title" => "",
            "body" => "",
            "friend_point" => 0,
            "bg_id" => 0,
            "effect_id" => 0,
            "start_at" => "",
            "end_at" => "",
            "platform" => 0,
            "withoutnew" => 0,
        ];
        $response = $container->view->render(
            $response,
            "admin/notices/create.php",
            [
                "app" => $app,
                "params" => $params
            ]
        );
        return $response;
    }

    /**
     * POST /admin/notices
     * @param \Slim\Slim $app
     */
    public static function post(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $params = $request->getParams();
        $params['start_at:formatted'] = Str::formatTime($params['start_at'], 'Y-m-d\TH:i:sP');
        $params['end_at:formatted'] = Str::formatTime($params['end_at'], 'Y-m-d\TH:i:sP');
        $errorMap = self::validateParams($params);

        if (!empty($errorMap)) {
            $container->flash->addMessageNow('errors', $errorMap);
            $flash = $container->flash->getMessages();
            $response = $container->view->render(
                $response,
                "admin/notices/create.php",
                [
                    "app" => $app,
                    "params" => $params,
                    "flash" => $flash,
                ]
            );

            return $response;
        }

        // 保存
        $entity = ClusterORM::for_table("notices")
                ->create([]);
        self::saveEntity($entity, $params);

        $container->flash->addMessage('success', "保存しました.");
        $response = $response->withStatus(302)->withHeader('Location', "/admin/notices/{$entity->id}");
        return $response;
    }

    /**
     * GET /admin/notices/:id
     * @param \Slim\Slim $app
     * @param int        $id
     */
    public static function get(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $entity = self::getEntity($args['id']);
        if (!$entity) {
            $container->flash->addMessage('error', "存在しないIDです.");
            $response = $response->withStatus(302)->withHeader('Location', "/admin/notices");
            return $response;
        }

        $flash = $container->flash->getMessages();
        $response = $container->view->render(
            $response,
            "admin/notices/get.php",
            [
                "app" => $app,
                "id" => $args['id'],
                "entity" => $entity,
                "building" => MasterORM::for_table("buildings")->get_one($entity->building_id),
                "speciality" => MasterORM::for_table("specialities")->get_one($entity->speciality_id),
                "commander" => MasterORM::for_table("commanders")->get_one($entity->commander_id),
                "item" => MasterORM::for_table("items")->get_one($entity->item_id),
                "flash" => $flash,
            ]
        );
        return $response;
    }

    /**
     * GET /admin/notices/:id/edit
     * @param \Slim\Slim $app
     * @param int        $id
     */
    public static function edit(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $entity = self::getEntity($args['id']);
        if (!$entity) {
            $container->flash->addMessage('error', "存在しないIDです.");
            $response = $response->withStatus(302)->withHeader('Location', "/admin/notices");
            return $response;
        }
        $params = [
            "title" => $entity->title,
            "body" => $entity->body,
            "zeni" => $entity->zeni,
            "food" => $entity->food,
            "ryo" => $entity->ryo,
            "friend_point" => $entity->friend_point,
            "building_id" => $entity->building_id,
            "speciality_id" => $entity->speciality_id,
            "commander_id" => $entity->commander_id,
            "item_id" => $entity->item_id,
            "bg_id" => $entity->bg_id,
            "effect_id" => $entity->effect_id,
            "start_at" => Str::formatTime($entity->start_at, 'Y-m-d\TH:i:s'),
            "end_at" => Str::formatTime($entity->end_at, 'Y-m-d\TH:i:s'),
            "platform" => $entity->platform,
            "withoutnew" => $entity->withoutnew,
        ];

        $response = $container->view->render(
            $response,
            "admin/notices/edit.php",
            [
                "app" => $app,
                "id" => $args['id'],
                "entity" => $entity,
                "params" => $params
            ]
        );
        return $response;
    }

    /**
     * PUT /admin/notices/:id/edit
     * @param \Slim\Slim $app
     * @param int        $id
     */
    public static function put(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $params = $request->getParams();
        $params['start_at:formatted'] = Str::formatTime($params['start_at'], 'Y-m-d\TH:i:sP');
        $params['end_at:formatted'] = Str::formatTime($params['end_at'], 'Y-m-d\TH:i:sP');
        $errorMap = self::validateParams($params);

        $entity = self::getEntity($args['id']);
        if (!$entity) {
            $container->flash->addMessageNow('errors', "存在しないIDです.");
            $flash = $container->flash->getMessages();
            $response = $container->view->render(
                $response,
                "admin/notices/edit.php",
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
        // 保存
        self::saveEntity($entity, $params);

        $container->flash->addMessage('success', "更新しました.");
        $response = $response->withStatus(302)->withHeader('Location', "/admin/notices/{$args['id']}");
        return $response;
    }

    /**
     * DELETE /admin/notices/:id
     * @param \Slim\Slim $app
     * @param int        $id
     */
    public static function delete(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $entity = self::getEntity($args['id']);
        if (!$entity) {
            $container->flash->addMessage('error', "存在しないIDです.");
            $response = $response->withStatus(302)->withHeader('Location', "/admin/notices");
            return $response;
        }
        $entity->delete();
        $container->flash->addMessage('success', "削除しました.");
        $response = $response->withStatus(302)->withHeader('Location', "/admin/notices");
        return $response;
    }

    /**
     * 指定したIDのお知らせを返す.
     * @param  int        $id
     * @return ClusterORM
     */
    protected static function getEntity($id)
    {
        $entity = ClusterORM::for_table("notices")
            ->find_one($id);

        return $entity;
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
                "title":                {"type":"string", "required":true, "minLength":2, "maxLength":45},
                "body":                 {"type":"string", "required":true, "minLength":2},
                "zeni":                 {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "food":                 {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "ryo":                  {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "friend_point":         {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "building_id":          {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "speciality_id":        {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "commander_id":         {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "item_id":              {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "bg_id":                {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "effect_id":            {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "start_at:formatted":   {"type":"string", "format":"date-time"},
                "end_at:formatted":     {"type":"string", "format":"date-time"},
                "platform":             {"type":"string", "required":true, "enum": ["0", "1", "2"]},
                "withoutnew":           {"type":"string", "required":true, "enum": ["0", "1"]}
            }
        }';

        $errors = ValidationService::validateJsonForAdmin((object) $params, $schema);
        $app = \App\App::getInstance();
        $errorMap = [];
        foreach ($errors as $error) {
            $errorMap[$error['property']] = $error['message'];
        }

        // 値のチェック.
        if (mb_strlen($params["body"],"UTF-8") > 255) {
            $errorMap['body'] = " 入力できる文字数は255文字までです(".mb_strlen($params["body"],"UTF-8").")";
        }
        if ($params["friend_point"] < 0 || $params["friend_point"] > PHP_INT_MAX) {
            $errorMap['friend_point'] = " must be between 0 and 2147483647.";
        }

        return $errorMap;
    }

    /**
     * お知らせを保存して返す.
     * @param  ClusterORM $entity
     * @param  array      $paramas
     * @return ClusterORM
     */
    protected static function saveEntity($entity, $params)
    {
        $entity->title = $params["title"];
        $entity->body = $params["body"];
        $entity->friend_point = $params["friend_point"];
        $entity->bg_id = $params["bg_id"];
        $entity->effect_id = $params["effect_id"];
        $entity->start_at = Str::formatTime($params["start_at"]);
        $entity->end_at = Str::formatTime($params["end_at"]);
        $entity->platform = $params["platform"];
        $entity->withoutnew = $params["withoutnew"];
        $entity->save();
        // キャッシュをクリア.
        MasterORM::for_table('notices')->clearCache($entity->id);

        return $entity;
    }
}
