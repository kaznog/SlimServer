<?php
namespace App\Controllers\Admin;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\Models\ClusterORM;
use \App\Models\MasterORM;
use \App\Models\Pager;
use \App\Models\GameParameter;
use \App\Models\Prefectures;
use \App\Services\ValidationService;
use \Utils\Str;
use \Utils\ScrambleID;
use \App\App;

/**
 * 管理画面: players, players_identities 編集.
 */
class PlayersController
{
    const LIMIT = 100;

    /**
     * GET /admin/players?q=...
     */
    public static function index(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $params = $request->getParams();
        // 検索キーワード
        $q = $params["q"] ?? "";
        // レベル
        $fv = $params["fv"] ?? "";
        // プラットフォーム
        $fp = $params["fp"] ?? 0;
        // アカウント種類
        //$fa = $app->request->get("fa");
        //$fa = isset($fa) ? $fa : 0;

        $page = $params["page"] ?? null;
        $page = $page ? (int) $page : 0;
        $limit = self::LIMIT;
        $offset = self::LIMIT * $page;
        $count = 0;
        $playersIdentities = [];

        $where_clause = "";
        $where_param = [];

        // 検索キーワード
        if (!empty($q)) {
            $where_clause = "id = ? OR name LIKE ?";
            $where_param[] = $q;
            $where_param[] = "%{$q}%";
            if (is_numeric($q)) {
                $where_clause .= " OR id = ?";
                $where_param[] = ScrambleID::desucramble($q);
            }
        }
        // レベル
        if (is_numeric($fv)) {
            if (strlen($where_clause) > 0) {
                $where_clause .= " AND level = ?";
            } else {
                $where_clause = "level = ?";
            }
            $where_param[] = $fv;
        }
        // プラットフォーム
        if ($fp > 0) {
            if (strlen($where_clause) > 0) {
                $where_clause .= " AND device_platform = ?";
            } else {
                $where_clause = "device_platform = ?";
            }
            $where_param[] = $fp;
        }
        // アカウント種類
        //if ($fa > 0) {
        //    if (strlen($where_clause) > 0) {
        //        $where_clause .= " AND kind = ?";
        //    } else {
        //        $where_clause = "kind = ?";
        //    }
        //    $where_param[] = $fa;
        //}
        // 検索
        if (strlen($where_clause) > 0) {
            $count = ClusterORM::for_table("players_identities")
                ->select_shard(null)
                ->where_raw($where_clause, $where_param)
                ->count();
            $playersIdentities = ClusterORM::for_table("players_identities")
                ->select_shard(null)
                ->where_raw($where_clause, $where_param)
                ->offset($offset)
                ->limit($limit)
                ->order_by_asc("id")
                ->find_many();
        } else {
            $count = ClusterORM::for_table("players_identities")
                ->select_shard(null)
                ->count();
            $playersIdentities = ClusterORM::for_table("players_identities")
                ->select_shard(null)
                ->offset($offset)
                ->limit($limit)
                ->order_by_asc("id")
                ->find_many();
        }
        //$app->log->debug(var_export($playersIdentities, true));
        $response = $container->view->render(
            $response,
            "admin/players/index.php",
            [
                "app" => $app,
                "q" => $q,
                "fp" => $fp,
                "fv" => $fv,
                /*"fa" => $fa,*/
                /*"pager" => new Pager("/admin/players?q={$q}&fs={$fs}&fv={$fv}&fp={$fp}&fa={$fa}", $limit, $count, $page),*/
                "pager" => new Pager("/admin/players?q={$q}&fv={$fv}&fp={$fp}", $limit, $count, $page),
                "count" => $count,
                "playersIdentities" => $playersIdentities
            ]
        );
        return $response;
    }

    /**
     * GET /admin/players/:playerId
     */
    public static function get(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $player_id = $args['id'];
        $playersIdentity = ClusterORM::for_table("players_identities")->find_one($player_id);
        $app->logger->addNotice("admin/players/{$player_id}/get playersIdentity:" . serialize($playersIdentity));
        $player = ClusterORM::for_table("players")
                ->select_shard($player_id)
                ->find_one($player_id);
        $flash = $container->flash->getMessages();
        // 最終チェックインエリア
        //if ($player->latest_area_id) {
        //    $player->latest_area = MasterORM::for_table('areas')->find_one($player->latest_area_id);
        //}
        // 累積課金額
        //$totalSales = ClusterORM::for_table("players_purchase_histories")
        //    ->where_equal("player_id", $playerId)
        //    ->select_expr('sum(price)', 'sales')
        //    ->find_one();
        //$player->total_sales = $totalSales->sales ? $totalSales->sales : 0;

        // players_saves
        //$playersSaves = ClusterORM::for_table("players_saves")
        //    ->where_equal("player_id", $playerId)
        //    ->find_one();

        $response = $container->view->render(
            $response,
            "admin/players/get.php",
            [
                "app" => $app,
                "flash" => $flash,
                "playerId" => $player_id,
                "scrambledId" => ScrambleID::scramble($player_id),
                "player" => $player,
                "playersIdentity" => $playersIdentity,
                /*"playersSaves" => $playersSaves*/
            ]
        );
        return $response;
    }

    /**
     * GET /admin/players/:playerId/edit
     */
    public static function edit(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $player_id = $args['id'];
        $playersIdentity = ClusterORM::for_table("players_identities")->find_one($player_id);
        $player = ClusterORM::for_table("players")
                ->select_shard($player_id)
                ->find_one($player_id);

        $params = [
            "name"                  => $playersIdentity->name,
            "gender"                => $playersIdentity->gender,
            "device_platform"       => $playersIdentity->device_platform,
            "push_registration_id"  => $playersIdentity->push_registration_id,
            "last_login_at_today"   => Str::formatTime($playersIdentity->last_login_at, 'Y-m-d\TH:i:s'),
            "level"                 => $player->level,
            "level_exp"             => $player->level_exp,
            "friend_point"          => $player->friend_point,
            "liked"                 => $player->liked,
            "extra_friend_capacity" => $player->extra_friend_capacity,
            "total_movement"        => $player->total_movement,
            "last_checkin_at"       => Str::formatTime($player->last_checkin_at, 'Y-m-d\TH:i:s'),
            "last_login_at"         => Str::formatTime($player->last_login_at, 'Y-m-d\TH:i:s'),
            "login_count"           => $player->login_count,
            "login_streak_count"    => $player->login_streak_count,
            "status"                => $playersIdentity->status,
            "tutorial1"             => $player->tutorial1,
            "tutorial2"             => $player->tutorial2,
            "tutorial3"             => $player->tutorial3,
            "tutorial4"             => $player->tutorial4,
            "tutorial5"             => $player->tutorial5,
            "tutorial6"             => $player->tutorial6,
            "tutorial7"             => isset($player->tutorial7) ? $player->tutorial7 : 0,
            "tutorial8"             => isset($player->tutorial8) ? $player->tutorial8 : 0,
        ];

        $response = $container->view->render(
            $response,
            "admin/players/edit.php",
            [
                "app" => $app,
                "playerId" => $player_id,
                "player" => $player,
                "playersIdentity" => $playersIdentity,
                "params" => $params
            ]
        );
        return $response;
    }

    /**
     * PUT /admin/players/:playerId
     * @param \Slim\Slim $app
     */
    public static function put(Request $request, Response $response, Array $args)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $player_id = $args['id'];
        $params = $request->getParams();
        $params['last_checkin_at:formatted']      = Str::formatTime($params['last_checkin_at'], 'Y-m-d\TH:i:sP');
        $params['last_login_at_today:formatted']  = Str::formatTime($params['last_login_at_today'], 'Y-m-d\TH:i:sP');
        $params['last_login_at:formatted']        = Str::formatTime($params['last_login_at'], 'Y-m-d\TH:i:sP');

        $errorMap = self::validateParams($params);

        $playersIdentity = ClusterORM::for_table("players_identities")->find_one($player_id);
        $player = ClusterORM::for_table("players")
                ->select_shard($player_id)
                ->find_one($player_id);

        if (!empty($errorMap)) {
            $container->flash->addMessageNow('error', $errorMap);
            $flash = $container->flash->getMessages();
            $app->render(
                "admin/players/edit.php",
                [
                    "app" => $app,
                    "playerId" => $player_id,
                    "player" => $player,
                    "playersIdentity" => $playersIdentity,
                    "params" => $params,
                    "flash" => $flash
                ]
            );

            return;
        }

        // 保存.
        self::saveEntry($playersIdentity, $player, $params);

        $container->flash->addMessage('success', "保存しました.");
        $response = $response->withStatus(302)->withHeader('Location', "/admin/players/{$player_id}");
        return $response;
    }

    /**
     * @param  int   $id
     * @param  array $params
     * @return array エラー配列
     */
    protected static function validateParams($params)
    {
        $schema = '
        {
            "type":"object",
            "properties":{
                "name":                             {"type":"string", "required": true, "minLength":1, "maxLength":45},
                "gender":                           {"type":"string", "required":true, "enum": ["1", "2"]},
                "device_platform":                  {"type":"string", "required":true, "enum": ["1", "2"]},
                "push_registration_id":             {"type":"string"},
                "level":                            {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "level_exp":                        {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "friend_point":                     {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "liked":                            {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "extra_friend_capacity":            {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "total_movement":                   {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "last_checkin_at:formatted":        {"type":["string","null"], "format":"date-time"},
                "last_login_at_today:formatted":    {"type":["string","null"], "format":"date-time"},
                "last_login_at:formatted":          {"type":["string","null"], "format":"date-time"},
                "login_count":                      {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "login_streak_count":               {"type":"string", "required":true, "format":"regex", "pattern": "[0-9]+"},
                "status":                           {"type":"string", "required":true, "enum": ["0", "1", "2", "3"]}
            }
        }';

        $errors = ValidationService::validateJsonForAdmin((object) $params, $schema);
        $errorMap = [];
        foreach ($errors as $error) {
            $errorMap[$error['property']] = $error['message'];
        }

        // 値のチェック.
        if ($params["friend_point"] < 0 || $params["friend_point"] > PHP_INT_MAX) {
            $errorMap['friend_point'] = " must be between 0 and 2147483647.";
        }
        if ($params["level"] < 0) {
            $errorMap['level'] = " must be greater than 0.";
        }
        if ($params["level_exp"] < 0) {
            $errorMap['level_exp'] = " must be greater than 0.";
        }
        if ($params["liked"] < 0) {
            $errorMap['liked'] = " must be greater than 0.";
        }
        // $maxExtraFriendCapacity = GameParameter::get('EXTRA_FRIEND_CAPACITY_MAX');
        // if ($params["extra_friend_capacity"] < 0 || $params["extra_friend_capacity"] > $maxExtraFriendCapacity) {
        if ($params["extra_friend_capacity"] < 0) {
            $errorMap['extra_friend_capacity'] = " must be between 0 and {$maxExtraFriendCapacity}.";
        }
        if ($params["total_movement"] < 0) {
            $errorMap['total_movement'] = " must be greater than 0.";
        }
        if ($params["login_count"] < 0) {
            $errorMap['login_count'] = " must be greater than 0.";
        }
        if ($params["login_streak_count"] < 0) {
            $errorMap['login_streak_count'] = " must be greater than 0.";
        }

        // if ($params["tutorial1"] < 0 || $params["tutorial1"] > 512) {
        //     $errorMap['tutorial1'] = " must be between 0 and 512.";
        // }
        // if ($params["tutorial2"] < 0 || $params["tutorial2"] > 512) {
        //     $errorMap['tutorial2'] = " must be between 0 and 512.";
        // }
        // if ($params["tutorial3"] < 0 || $params["tutorial3"] > 512) {
        //     $errorMap['tutorial3'] = " must be between 0 and 512.";
        // }
        // if ($params["tutorial4"] < 0 || $params["tutorial4"] > 512) {
        //     $errorMap['tutorial4'] = " must be between 0 and 512.";
        // }
        // if ($params["tutorial5"] < 0 || $params["tutorial5"] > 512) {
        //     $errorMap['tutorial5'] = " must be between 0 and 512.";
        // }
        // if ($params["tutorial6"] < 0 || $params["tutorial6"] > 512) {
        //     $errorMap['tutorial6'] = " must be between 0 and 512.";
        // }
        // if ($params["tutorial7"] < 0 || $params["tutorial7"] > 512) {
        //     $errorMap['tutorial7'] = " must be between 0 and 512.";
        // }
        // if ($params["tutorial8"] < 0 || $params["tutorial8"] > 512) {
        //     $errorMap['tutorial8'] = " must be between 0 and 512.";
        // }

        //$app = App::getInstance();
        //$app->logger->addDebug(var_export($errorMap, true));
        return $errorMap;
    }

    /**
     * お知らせを保存して返す.
     * @param  ClusterORM $entry
     * @param  array      $paramas
     * @return ClusterORM
     */
    protected static function saveEntry($playersIdentity, $player, $params)
    {
        $playersIdentity->name = $params["name"];
        $playersIdentity->gender = $params["gender"];
        $playersIdentity->device_platform = $params["device_platform"];
        $playersIdentity->push_registration_id = $params["push_registration_id"];
        $playersIdentity->level = $params["level"];
        $player->last_login_at = Str::formatTime($params["last_login_at_today"]);
        $playersIdentity->status = $params["status"];
        $playersIdentity->save();

        $player->level = $params["level"];
        $player->level_exp = $params["level_exp"];
        $player->friend_point = $params["friend_point"];
        $player->liked = $params["liked"];
        $player->extra_friend_capacity = $params["extra_friend_capacity"];
        $player->last_checkin_at = Str::formatTime($params["last_checkin_at"]);
        $player->last_login_at = Str::formatTime($params["last_login_at"]);
        $player->login_count = $params["login_count"];
        $player->login_streak_count = $params["login_streak_count"];
        $player->save();

        return $playersIdentity;
    }
}
