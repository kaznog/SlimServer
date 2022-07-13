<?php
namespace App\Models;

class BridgeSessionObj
{
	public $device_platform;
	public $world_id;
	public $native_session_id;
	public $shared_secrity_key;
	public $bridge_user_id;
	public $regist;
	public $sqex_id;
	public $tag_name;
	public $native_tag_name;
	public function __construct()
	{
		$this->clear();
	}

	public function clear()
	{
		$this->device_platform = 0;
		$this->world_id = null;
		$this->native_session_id = null;
		$this->shared_secrity_key = null;
		$this->bridge_user_id = null;
		$this->regist = false;
		$this->sqex_id = null;
		$this->tag_name = null;
		$this->native_tag_name = null;
	}
}