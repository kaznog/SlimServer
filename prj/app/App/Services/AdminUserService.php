<?php
namespace App\Services;

use \App\App;
use \Utils\StringEncrypt;
use \Utils\Mail;

/**
 * 管理ページユーザ
 */
class AdminUserService
{    
    /*
     * ログを取得
     * @param string $query
     */
    public static function getLog($query)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $filename = $container['settings']['admin.users']['log.path'];
        $sqlite_bd = new \SQLite3($filename, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $create_sql = 'CREATE TABLE IF NOT EXISTS logs (id INTEGER PRIMARY KEY AUTOINCREMENT, user TEXT, method TEXT, pattern TEXT, comments TEXT, created_at TEXT)';
        $sqlite_bd->exec($create_sql);
        
        $rows = $sqlite_bd->query($query);
        
        return self::translateArray($rows);
    }

    /*
     * 権限DBファイルの元をコピーして、ローカルに設置する.
     */
    public static function copyRoles()
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $dstFilename = $container['settings']['admin.users']['roles_dst.path'];
        if (!file_exists($dstFilename)) {
            $app->logService->logger('admin')->addDebug($dstFilename . " is not exists!");
            $srcFilename = APP_ROOT . $container['settings']['admin.users']['roles.path'];
            copy($srcFilename, $dstFilename);
        }
    }
    
    /*
     * 権限DBファイルの元をコピーして、ローカルに設置する.
     */
    public static function getPatterns()
    {
        self::copyRoles();
        
        $app = App::getInstance();
        $container = $app->getContainer();
        $filename = APP_ROOT . $container['settings']['admin.users']['roles.path'];
        $sqlite_bd = new \SQLite3($filename, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $create_sql = 'CREATE TABLE IF NOT EXISTS access_patterns (id INTEGER PRIMARY KEY AUTOINCREMENT, method TEXT, pattern TEXT, comments TEXT)';
        $sqlite_bd->exec($create_sql);
        
        $rows = $sqlite_bd->query("SELECT * FROM access_patterns");
        
        return self::translateArray($rows);
    }
    
    /*
     * 指定したパラメータの権限情報を取得する.
     * @param $pattern パターン
     * @param $method メソッド
     */
    public static function getPattern($pattern, $method)
    {
        $patterns = self::getPatterns();
        foreach ($patterns as $p) {
            if ($p['pattern'] == $pattern && $p['method'] == $method) {
                return $p;
            }
        }
        return [];
    }
    
    /*
     * 権限を追加更新する.
     * @param $role_id
     * @param $role_name
     */
    public static function updateRole($role_id, $role_comments)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $filename = $container['settings']['admin.users']['roles_dst.path'];
        $sqlite_bd = new \SQLite3($filename, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $create_sql = 'CREATE TABLE IF NOT EXISTS access_patterns (id INTEGER PRIMARY KEY AUTOINCREMENT, method TEXT, pattern TEXT, comments TEXT)';
        $sqlite_bd->exec($create_sql);
        
        $rows = $sqlite_bd->query("SELECT * FROM roles WHERE id = '{$role_id}'");
        $array = self::translateArray($rows);

        $query = "";
        if (count($array) == 0) {
            // 新規追加.
            $query = "INSERT INTO roles(id, comments) VALUES('{$role_id}', '{$role_comments}')";
            $sqlite_bd->exec($query);
        }
        else {
            // 更新.
            $query = "UPDATE roles SET comments = '{$role_comments}' WHERE id = '{$role_id}'";
            $sqlite_bd->query($query);
        }
    }
    
    /*
     * 権限を削除する.
     * @param $role_id
     */
    public static function deleteRole($role_id)
    {
        // roleが使われているか確認する.
        $users = self::getAdminUsers();
        foreach ($users as $user) {
            if ($user['role'] == $role_id) {
                // 使用済のroleがある場合削除しない.
                return;
            }
        }
        
        $app = App::getInstance();
        $container = $app->getContainer();
        $filename = $container['settings']['admin.users']['roles_dst.path'];
        $sqlite_bd = new \SQLite3($filename, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $create_sql = 'CREATE TABLE IF NOT EXISTS access_patterns (id INTEGER PRIMARY KEY AUTOINCREMENT, method TEXT, pattern TEXT, comments TEXT)';
        $sqlite_bd->exec($create_sql);
        
        $rows = $sqlite_bd->query("SELECT * FROM roles WHERE id = '{$role_id}'");
        $array = self::translateArray($rows);

        if (count($array) !== 0) {
            $query = "DELETE FROM ignore_pages WHERE role_id = '{$role_id}'";
            $sqlite_bd->exec($query);
            
            $query = "DELETE FROM roles WHERE id = '{$role_id}'";
            $sqlite_bd->exec($query);
        }
    }
    
    /*
     * 指定したignore_pageを追加する.
     * @param intgger $role_id
     * @param int[] $page_ids
     */
    public static function updateIgnorePages($role_id, $page_ids)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $filename = $container['settings']['admin.users']['roles_dst.path'];
        $sqlite_bd = new \SQLite3($filename, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $create_sql = 'CREATE TABLE IF NOT EXISTS access_patterns (id INTEGER PRIMARY KEY AUTOINCREMENT, method TEXT, pattern TEXT, comments TEXT)';
        $sqlite_bd->exec($create_sql);
        
        // 先にignore_pageを削除.
        $sqlite_bd->exec("DELETE FROM ignore_pages WHERE role_id = '{$role_id}'");
        
        $patterns = self::getPatterns();
        if (count($patterns) !== 0) {
            // ignore_pageを追加していく.
            foreach ($patterns as $pattern) {
                if (in_array($pattern['id'], $page_ids)) {
                    $query = "INSERT INTO ignore_pages(role_id,pattern,method,comments) VALUES('{$role_id}','{$pattern['pattern']}','{$pattern['method']}','{$pattern['comments']}')";
                    $sqlite_bd->exec($query);
                }
            }
        }
    }
    
    /**
     * 管理ユーザ取得
     * @return string
     */
    public static function getMyUid()
    {
        $app = App::getInstance();
        return $app->getContainer()['cookie']->get('uid');
    }
    
    /**
     * 管理ユーザ一覧取得
     * @return array
     */
    public static function getAdminUsers()
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $admin_users_filename = $container['settings']['admin.users']['account.path'];
        $admin_user_settings = [];
        if (file_exists($admin_users_filename)) {
            $admin_users_json = file_get_contents($admin_users_filename);
            $admin_user_settings = json_decode($admin_users_json, true);
            return $admin_user_settings['users'];
        }
        return $admin_user_settings;
    }
    
    /**
     * 管理ユーザ取得
     * @param string $user_id
     * @return array
     */
    public static function getAdminUser($user_id)
    {
        $users = self::getAdminUsers();
        foreach ($users as $user) {
            if ($user['user_id'] == $user_id) {
                return $user;
            }
        }
        return null;
    }
    
    /**
     * 管理ユーザ取得
     * @param string $user_id
     * @return array
     */
    public static function getMyAdminUser()
    {
        $user_id = self::getMyUid();
        $users = self::getAdminUsers();
        foreach ($users as $user) {
            if ($user['user_id'] == $user_id) {
                return $user;
            }
        }
        return null;
    }
    
    /**
     * 管理ユーザ存在確認
     * @param string $user_id
     * @return array
     */
    public static function existsAdminUser($user_id)
    {
        return (self::getAdminUser($user_id) != null);
    }
    
    /**
     * 権限一覧取得
     * @return array
     */
    public static function getRoles()
    {
        self::copyRoles();
        
        $app = App::getInstance();
        $container = $app->getContainer();
        $filename = $container['settings']['admin.users']['roles_dst.path'];
        $sqlite_bd = new \SQLite3($filename, SQLITE3_OPEN_READWRITE);
        
        $results = [];
        $rows_role = $sqlite_bd->query ('SELECT * FROM roles');
        $rows_ignore = $sqlite_bd->query ('SELECT * FROM ignore_pages');
        
        $pages = [];
        while ($ignore = $rows_ignore->fetchArray()) {
            $ign['id'] = $ignore['id'];
            $ign['pattern'] = $ignore['pattern'];
            $ign['method'] = $ignore['method'];
            $ign['comments'] = $ignore['comments'];
            $pages[$ignore['role_id']][] = $ign;
        }
        
        while ($role = $rows_role->fetchArray()) {
            $result['id'] = $role['id'];
            $result['comments'] = $role['comments'];
            $result['pages'] = (isset($pages[$role['id']])) ? $pages[$role['id']] : [];
            $results[$role['id']] = $result;
        }
        
        return $results;
    }
    
    /**
     * 権限コメント一覧取得
     * @return array
     */
    public static function getRolesComments()
    {
        $roles = self::getRoles();
        $comments = [];
        foreach ($roles as $idx => $role) {
            $comments[$idx] = $role['comments'];
        }
        return $comments;
    }
    
    /**
     * 権限取得
     * @param integer $user_id
     * @return integer/null
     */
    public static function getRole($user_id)
    {
        $users = self::getAdminUsers();
        
        foreach ($users as $user) {
            if ($user['user_id'] == $user_id) {
                return $user['role'];
            }
        }
        
        return null;
    }
    
    /**
     * 管理者ユーザのログを追加.
     * StreamHandlerに任せるとpathが取得できないのでServiceで行う
     * @
     * @param string $message
     */
    public static function addLog($request = null, $message = " ")
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $method  = ($request !== null) ? $request->getMethod() : $_SERVER['REQUEST_METHOD'];
        $pattern = ($request !== null) ? $request->getAttribute('route')->getPattern() : $_SERVER['REQUEST_URI'];
        
        if ($message == " ") {
            $params = $request->getQueryParams();;
            if ($method != "GET" && $method != "POST" && $method != "PUT" && $method != "DELETE") {
                $message .= "Other Method.";
            }
            
            foreach ($params as $idx => $param) {
                if (strpos($idx, 'pass') !== false) {
                    // パスワードは見せない //
                    $message .= $idx . ":" . "****" . "/";
                }
                else {
                    if (is_array($param)) {
                        $message .= "{$idx}[";
                        foreach ($param as $p) {
                            $message .= $p . ",";
                        }
                        $message .= ']';
                    }
                    else {
                        $message .= $idx . ":" . $param . "/";
                    }
                }
            }
        }
        
        $container = $app->getContainer();
        $filename = $container['settings']['admin.users']['log.path'];
        $sqlite_bd = new \SQLite3($filename, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $create_sql = 'CREATE TABLE IF NOT EXISTS logs (id INTEGER PRIMARY KEY AUTOINCREMENT, user TEXT, method TEXT, pattern TEXT, comments TEXT, created_at TEXT)';
        $sqlite_bd->exec($create_sql);
        
        $uid = self::getMyUid();
        $datetime = date('Y-m-d H:i:s');
        $sqlite_bd->exec("INSERT INTO logs(user, method, pattern, comments, created_at) VALUES('{$uid}', '{$method}', '{$pattern}', '{$message}', '{$datetime}')");
        
    }

    /**
     * パスワード確認.
     * @param string $user_id
     * @param string $password ハッシュ変換前
     * @return boolean
     */
    public static function checkPassword($user_id, $password)
    {
        $user = self::getAdminUser($user_id);
        $app = App::getInstance();
        $app->logService->logger('admin')->addDebug("user['password']:" . $user['password']);
        $app->logService->logger('admin')->addDebug("password:" . $password);
        $decPass = self::decodePassword($user['password']);
        $app->logService->logger('admin')->addDebug("decode password:" . $decPass);

        return $decPass == $password;
    }
    
    
    /**
     * 管理者ユーザを更新、無ければ追加する
     * @param string $user_id
     * @param string $password ハッシュ変換前
     * @param integer $role
     * @return string errorMsg void = success
     */
    public static function updateAdminUser($user_id, $password = null, $role = null)
    {
        $error_msg = "";
        
        // パスワードか権限は入力されていない場合は、既存のものを使用する.
        $before = self::getAdminUser($user_id);
        if ($before != null) {
            $password   = (isset($password)) ? $password : self::decodePassword($before['password']);
            $role       = (isset($role))     ? $role     : $before['role'];
        }
        
        
        if ($user_id === null && $user_id === "") {
            $error_msg = $error_msg."ユーザIDが入力されていません。<br>";
        }
        
        if ($password === null && $password === "") {
            $error_msg = $error_msg."パスワードが入力されていません。<br>";
        }
        
        if ($role === null && $role === "") {
            $error_msg = $error_msg."権限レベルが設定されていません。{$role}<br>";
        }
        
        if (mb_strlen($password) < 4) {
            $error_msg = $error_msg."パスワードは 4文字以上の半角英数字を設定してください。<br>";
        }
        
        if ($error_msg != "") {
            // 作成できない場合エラー.
            return $error_msg;
        }
        
        // 更新、追加処理.
        $app = App::getInstance();
        $container = $app->getContainer();
        $admin_users_filename = $container['settings']['admin.users']['account.path'];
        
        // ファイル・ディレクトリが無ければ作成する.
        if (!file_exists($admin_users_filename)) {
            $path = dirname($admin_users_filename);
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            $default_text = mb_convert_encoding('{"users": []}', 'UTF-8');
            file_put_contents($admin_users_filename, $default_text, FILE_TEXT);
        }
        
        $admin_users_json = file_get_contents($admin_users_filename);
        $admin_user_settings = json_decode($admin_users_json, true);
        
        $password = self::encodePassword($password);
        
        if (self::existsAdminUser($user_id)) {
            // 存在するので更新.
            foreach ($admin_user_settings['users'] as &$user) {
                if ($user['user_id'] == $user_id) {
                    $user['password']   = $password;
                    $user['role']       = $role;
                    break;
                }
            }
        }
        else {
            // 追加.
            $user_setting['user_id']    = $user_id;
            $user_setting['password']   = $password;
            $user_setting['role']       = $role;
            $admin_user_settings['users'][] = $user_setting;
        }        
        
        $admin_users_json = json_encode($admin_user_settings);
        $admin_users_json = self::indentJson($admin_users_json);
        file_put_contents($admin_users_filename, $admin_users_json);
        
        return $error_msg;
    }
    
    /**
     * 管理者ユーザを削除
     * @param string $user_id
     * @return string errorMsg void = success
     */
    public static function deleteAdminUser($user_id)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $admin_users_filename = $container['settings']['admin.users']['account.path'];
        $admin_users_json = file_get_contents($admin_users_filename);
        $admin_user_settings = json_decode($admin_users_json, true);
        $error_msg = "";
        
        $index = 0;
        foreach ($admin_user_settings['users'] as $idx => $user) {
            if ($user['user_id'] == $user_id) {
                $index = $idx;
                break;
            }
        }
        unset($admin_user_settings['users'][$index]);

        $admin_users_json = json_encode($admin_user_settings);
        $admin_users_json = self::indentJson($admin_users_json);
        file_put_contents($admin_users_filename, $admin_users_json);
    }
    
    /*
     * 文字列 -> ハッシュ
     * @param string $str
     * @return string hash
     */
    public static function encodePassword($str)
    {
        $str_encrypt = new StringEncrypt();
        $crypt_pw = 'hichewL0chew';
        $str = $str_encrypt->encrypt($str, $crypt_pw);
        $str = mb_convert_encoding(rtrim($str), 'UTF-8');
        return $str;
    }
    
    /*
     * ハッシュ -> 文字列
     * @param string $str
     * @return string hash
     */
    public static function decodePassword($str)
    {
        $str_encrypt = new StringEncrypt();
        $crypt_pw = 'hichewL0chew';
        $str = $str_encrypt->decrypt($str, $crypt_pw);
        $str = mb_convert_encoding(rtrim($str), 'UTF-8');
        return $str;
    }
    
    /*
     * jsonを整形する
     * @param string $json The original JSON string to process.
     * @return string Indented version of the original JSON string.
     */
    protected static function indentJson($json)
    {
        $result = '';
        $pos = 0;
        $strLen = strlen($json);
        $indentStr = '  ';
        $newLine = "\n";
        $prevChar = '';
        $outOfQuotes = true;
        for ($i = 0; $i <= $strLen; $i++) {
            // Grab the next character in the string.
            $char = substr($json, $i, 1);
            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;
                // If this character is the end of an element,
                // output a new line and indent the next line.
            } else if (($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
            // Add the character to the result string.
            $result .= $char;
            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
            $prevChar = $char;
        }
        return $result;
    }

    const USER_ROLE_SUPERADMIN = 0;
    /**
     * ユーザーJSONをsuper_adminへ送付する
     */
    public static function sendAdminUsers()
    {
        $adminUsers = self::getAdminUsers();
        $sendAdmins = array();
        foreach($adminUsers as $user) {
            if($user['role'] == self::USER_ROLE_SUPERADMIN) {
                $tmp = $user['user_id'];
                if (strpos($tmp, "@") !== false) {
                    $sendAdmins[] = $tmp;
                }
            }
        }
        
        // 送信者が誰もいない場合は処理しない.
        if (count($sendAdmins) == 0) { return; }
        
        $app = App::getInstance();
        $container = $app->getContainer();
        //$sendAdmins = implode(',', $sendAdmins);
        $subject = "update admin users env[{$app->mode}]";
        $message  = "update admin users env[{$app->mode}]<br>";
        $message .= "Please back up the attachment<br>";
        $send_file = $container['settings']['admin.users']['account.path'];
        
        Mail::sendGmail('apptool.information@gmail.com', 'hichewL0chew', $sendAdmins, $subject, $message, $send_file, "App Admin Tool");
    }
    
    /**
     * 権限ファイルをsuper_adminへ送付する
     */
    public static function sendAdminUsersRoles()
    {
        $adminUsers = self::getAdminUsers();
        $sendAdmins = array();
        foreach($adminUsers as $user) {
            if($user['role'] == self::USER_ROLE_SUPERADMIN) {
                $tmp = $user['user_id'];
                if (strpos($tmp, "@") !== false) {
                    $sendAdmins[] = $tmp;
                }
            }
        }
        
        // 送信者が誰もいない場合は処理しない.
        if (count($sendAdmins) == 0) { return; }
        
        $app = App::getInstance();
        $container = $app->getContainer();
        $subject = "update roles ignorepages env[{$app->mode}]";
        $message  = "update roles ignorepages env[{$app->mode}]<br>";
        $message .= "Please back up the attachment<br>";
        $send_file = $container['settings']['admin.users']['roles_dst.path'];
        
        Mail::sendGmail('apptool.information@gmail.com', 'hichewL0chew', $sendAdmins, $subject, $message, $send_file, "App Admin Tool");
    }
    
    /*
     * rowArray から array に型変換する.
     * @param rowArray SQlite のクエリ結果
     * @return array
     */
    protected static function translateArray($rowArray) {
        $results = [];
        while ($row = $rowArray->fetchArray()) {
            //$results[] = $row;
            $result = [];
            for ($idx = 0; $idx < $rowArray->numColumns(); $idx++) {
                $colName = $rowArray->columnName($idx);
                $result[$idx] = $row[$idx];
                $result[$colName] = $row[$colName];
            }
            $results[] = $result;
        }
        return $results;
    }
}



