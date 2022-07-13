<?php
namespace App\Models;

use \App\Services\DbClusterService;
use \App\App;

/**
 * シャーディングをサポートするORM(Idiorm)拡張.
 *
 * \ORM クラスでは、for_table() か　get_db() を呼んだときにだけ PDO オブジェクトが生成されるが,
 * 本拡張では、クエリ実行直前に、条件に応じて $connection_name を計算し、PDO オブジェクトを生成する.
 *
 * 条件から $connection_name を計算可能なのは、以下の場合.
 * * SELECT で、WHERE シャードキーカラム = ... で条件が指定されている場合.
 * * UPDATE, INSERT で、セットする値にシャードキーカラムが指定されている場合.
 *
 * オブジェクトにセットされた条件から計算できない場合は、select_shard() で、シャードIDを指定すること.
 * select_shard() で指定したシャードIDと、条件から計算したシャードIDが異なる場合、エラーが発生する.
 */
class ClusterORM extends \ORM
{
    // シャードID.
    protected $shardId = false;

    // マスターDB を使用するかどうか.
    protected $useMaster = true;

    /**
     * \ORM::for_table() を変更.
     * _setup_db() の実行を遅延させる.
     * @param  string $table_name
     * @param  string $connection_name Which connection to use
     * @return ORM
     */
    public static function for_table($table_name, $connection_name = null)
    {
        // $connection_name が明示的に指定されない限り _setup_db しない.
        if ($connection_name) {
            self::_setup_db($connection_name);
        }

        return new self($table_name, [], $connection_name);
    }

    public function rawexecute($query)
    {
        self::raw_execute($query,[],self::getConnectionName());
    }

    /**
     * シャードを指定する.
     * この時点で _setup_db() を実行する.
     * @param int $shardKey シャードIDを計算するための値.
     */
    public function select_shard($shardKey)
    {
        $this->shardId = $this->getShardId($shardKey);
        $connectionName = self::getConnectionName();
        $this->_connection_name = $connectionName;
        self::_setup_db($connectionName);

        return $this;
    }

    /**
     * スレーブDBを使用するかどうかを指定する.
     * user_replica() メソッドを呼ばなければ、マスターDBを使用する.
     * @bool $useReplica スレーブDBを使用する場合は true.
     */
    public function use_replica($useReplica = true)
    {
        $this->useMaster = ! $useReplica;
        $connectionName = self::getConnectionName();
        $this->_connection_name = $connectionName;
        self::_setup_db($connectionName);

        return $this;
    }

    /**
     * 接続先に指定されているデータベースに対する PDO オブジェクトを返す.
     * @return \PDO
     */
    public function current_db()
    {
        return self::get_db($this->_connection_name);
    }

    /**
     * \ORM#_build_where() を変更.
     */
    protected function _build_where()
    {
        $this->selectShardIdFromWhere();
        $connectionName = self::getConnectionName();
        $this->_connection_name = $connectionName;
        self::_setup_db($connectionName);

        return parent::_build_where();
    }

    /**
     * \ORM#_build_update() を変更.
     */
    protected function _build_update()
    {
        $this->selectShardIdFromData();
        $connectionName = self::getConnectionName();
        $this->_connection_name = $connectionName;
        self::_setup_db($connectionName);

        return parent::_build_update();
    }

    /**
     * \ORM#_build_insert() を変更.
     */
    protected function _build_insert()
    {
        $this->selectShardIdFromData();
        $connectionName = self::getConnectionName();
        $this->_connection_name = $connectionName;
        self::_setup_db($connectionName);

        return parent::_build_insert();
    }

    /**
     * DBコネクション名を取得する.
     * @return string コネクション名.
     */
    protected function getConnectionName()
    {
        $clusterName = DbClusterService::getClusterName($this->_table_name);

        return DbClusterService::getConnectionName($clusterName, $this->shardId, $this->useMaster);
    }

    /**
     * シャードIDを返す.
     * @param  int $shardKey シャード選択に使うカラムの値.
     * @return int $shardId シャードID シャーディングしないクラスターであれば nullを返す.
     */
    protected function getShardId($shardKey)
    {
        $clusterName = DbClusterService::getClusterName($this->_table_name);

        return DbClusterService::getShardId($clusterName, $shardKey);
    }

    /**
     * WHERE 条件からシャードIDを計算して返す.
     * "シャードキーカラム = ..." で WHERE 条件が指定されている場合に、シャードIDを計算する.
     * シャードIDを特定できなければ null を返す.
     * @return int シャードID
     */
    protected function selectShardIdFromWhere()
    {
        // 既にシャードが選択されていれば何もしない.
        if ($this->shardId !== false) {
            return;
        }

        $shardId = null;
        $shardKeyColumn = DbClusterService::getShardKeyColumn($this->_table_name);
        if ($shardKeyColumn) {
            $conditionFragments = [
                "{$this->_quote_identifier($shardKeyColumn)} = ?",
                "{$this->_quote_identifier($this->_table_name)}.{$this->_quote_identifier($shardKeyColumn)} = ?"
            ];
            foreach ($conditionFragments as $conditionFragment) {
                $shardKey = $this->searchWhereConditionValue($conditionFragment);
                if ($shardKey != null) {
                    break;
                }
            }
            if ($shardKey != null) {
                $shardId = $this->getShardId($shardKey);
                // $this->shardId とコンフリクト.
                if ($this->shardId !== false && $this->shardId !== $shardId) {
                    $app = App::getInstance();
                    $app->logger->addNotice("Inconsistend shard id({$shardId}) with the previously set value of {$this->shardId}.");
                    $app->responseArray = ["resultCode" => ResultCode::DB_SHARDING_ERROR];
                    $app->halt(500);
                }
            }
            if ($shardId === null) {
                // シャーディングするテーブルで、シャードIDが特定できない.
                $app = App::getInstance();
                $app->logger->addNotice("Couldn't select shard because shard id is null (table:{$this->_table_name}, shard key column:{$shardKeyColumn}, shard key:{$shardKey}).");
                $app->responseArray = ["resultCode" => ResultCode::DB_SHARDING_ERROR];
                $app->halt(500);
            }
        }
        $this->shardId = $shardId;
    }

    /**
     * $_where_conditions に、指定した $conditionFragment でセットされた値があれば返す.
     * @return mixed 値が指定されていなければ null.
     */
    protected function searchWhereConditionValue($conditionFragment)
    {
        foreach ($this->_where_conditions as $condition) {
            if ($condition[self::CONDITION_FRAGMENT] == $conditionFragment) {
                return $condition[self::CONDITION_VALUES][0];
            }
        }

        return null;
    }

    /**
     * $_data インスタンス変数からシャードIDを計算して返す.
     * シャードIDを特定できなければ null を返す.
     * @return int シャードID
     */
    protected function selectShardIdFromData()
    {
        $shardId = null;
        $shardKeyColumn = DbClusterService::getShardKeyColumn($this->_table_name);
        if ($shardKeyColumn) {
            if (isset($this->_data[$shardKeyColumn])) {
                $value = $this->_data[$shardKeyColumn];
                $shardId = $this->getShardId($value);
                // $this->shardId とコンフリクト.
                if ($this->shardId !== false && $this->shardId !== $shardId) {
                    $app = App::getInstance();
                    $app->logger->addNotice("Inconsistend shard id({$shardId}) with the previously set value of {$this->shardId}.");
                    $app->responseArray = ["resultCode" => ResultCode::DB_SHARDING_ERROR];
                    $app->halt(500);
                }
            }
        }
        $this->shardId = $shardId;
    }
}
