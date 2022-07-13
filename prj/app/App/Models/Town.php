<?php

namespace App\Models;

use \App\App;

class Town
{
	public $_modified;
	public $_orm;
    public function __construct($townId = -1)
	{
		if ($townId > -1) {
			$ret = $this->fetch($townId);
			if ($ret != ResultCode::SUCCESS) {
				$app = App::getInstance();
				$app->responseArray = [ "resultCode" => $ret ];
				$app->halt(200);
			}
		} else {
			$this->_orm = null;
		}
	}

	public function fetch($townId)
	{
		$this->_modified = false;
		$this->_orm = ClusterORM::for_table('towns')
		->select_shard(null)
		->find_one($townId);
		if ($this->_orm === false) {
			return ResultCode::TOWN_NOT_EXIST;
		}
		return ResultCode::SUCCESS;
	}

	public function slaveFetch($townId)
	{
		$this->modified = false;
		$this->_orm = ClusterORM::for_table('towns')
		->use_replica()
		->select_shard(null)
		->find_one($townId);
		if ($this->_orm === false) {
			return ResultCode::TOWN_NOT_EXIST;
		}
		return ResultCode::SUCCESS;
	}

	public function forUpdate($townId)
	{
		$this->_modified = false;
		$this->_orm = ClusterORM::for_table('towns')
		->select_shard(null)
		->raw_query("SELECT * FROM towns WHERE inquiry_id = ? for update", [$townId])
		->find_one();
	}

	public function forUpdateByTownId($townId)
	{
		$this->_modified = false;
		$this->_orm = ClusterORM::for_table('towns')
		->select_shard(null)
		->raw_query("SELECT * FROM towns WHERE id = ? for update", [$townId])
		->find_one();
	}

	public function getId()
	{
		if ($this->_orm == null) return -1;
		return $this->_orm->id;
	}

	public function getInqueryId()
	{
		if ($this->_orm == null) return null;
		return $this->_orm->inquiry_id;
	}

	public function getEntries()
	{
		if ($this->_orm == null) return 0;
		return $this->_orm->entries;
	}

	public function setEntries($entries)
	{
		if ($this->_orm == null) return;
		$this->_orm->entries = $entries;
		$this->_modified = true;
	}

	public function getMaxEntries()
	{
		if ($this->_orm == null) return 0;
		return $this->_orm->max_entries;
	}

	public function save()
	{
		if ($this->_orm == null) return;
		if ($this->_modified) {
			$this->_orm->save();
		}
	}

	public function toArray()
	{
		if ($this->_orm == null) {
			return [
				'id' => (int)-1,
				'inquiry_id' => '',
				'description' => '',
				'max_entries' => (int)0,
				'entries' => (int)0
			];
		} else {
			return [
				'id' => (int)$this->_orm->id,
				'inquiry_id' => $this->_orm->inquiry_id,
				'description' => $this->_orm->description,
				'max_entries' => (int)$this->_orm->max_entries,
				'entries' => (int)$this->_orm->entries
			];
		}
	}
}