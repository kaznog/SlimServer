<?php
namespace App\Services;

use \App\Models\ResultCode;
use \App\App;

/**
 * リクエストパラメタのバリデーション用サービスクラス.
 */
class ValidationService
{
    /**
     * リクエストJSON を JSON Schema を使用して検証する.
     * 検証に失敗した場合は直ちにHTTPレスポンス(200)を返す.
     * @param  stdClass $data   JSONをデコードして得たオブジェクト.
     * @param  string   $schema JSON スキーマ.
     * @return bool     検証に成功したら true.
     *                         @see http://json-schema.org
     *                         @see https://github.com/justinrainbow/json-schema
     */
    public static function validateJson($data, $schema)
    {
        $schemaObj = json_decode($schema);
        if (null == $schemaObj) {
            // スキーマが不正.
            $app = App::getInstance();
            $app->responseArray = ["resultCode" => ResultCode::INVALID_JSON_SCHEMA];
            $app->halt(500);
        }

        $validator = new \JsonSchema\Validator();
        $validator->check($data, $schemaObj);
        if (! $validator->isValid()) {
            $app = Slim::getInstance();
            file_put_contents("/tmp/debug.log", "ValidationService INVALID_PARAMETERS.\n", FILE_APPEND);
            $app->logger->addDebug(var_export($validator->getErrors(), true));
            $app->responseArray = ["resultCode" => ResultCode::INVALID_PARAMETERS, "data" => var_export($data, true), "message" => var_export($validator->getErrors(), true)];
            $app->halt(200);

            return false;
        }

        return true;
    }

    /**
     * リクエストJSON を JSON Schema を使用して検証する.
     * 検証に失敗した場合はエラー配列を返す.
     * @param  stdClass $data   JSONをデコードして得たオブジェクト.
     * @param  string   $schema JSON スキーマ.
     * @return array    $errors エラー配列. 検証に成功したら空配列.
     *                         @see http://json-schema.org
     *                         @see https://github.com/justinrainbow/json-schema
     */
    public static function validateJsonForAdmin($data, $schema)
    {
        $schemaObj = json_decode($schema);
        if (null == $schemaObj) {
            // スキーマが不正.
            $app = App::getInstance();
            $app->responseArray = ["resultCode" => ResultCode::INVALID_JSON_SCHEMA];
            $app->halt(500);
        }

        $validator = new \JsonSchema\Validator();
        $validator->check($data, $schemaObj);
        if (! $validator->isValid()) {
            return $validator->getErrors();
        }

        return [];
    }
}
