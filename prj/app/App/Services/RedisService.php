<?php
namespace App\Services;

use \App\App;

class RedisService
{
	const TYPE_KVS_MASTER    = 0;
	const TYPE_KVS_SLAVE     = 1;
	const TYPE_PUBSUB_MASTER = 2;
	const TYPE_PUBSUB_SLAVE  = 3;

	const COOKIE_CONNECTION_PREFIX 			= "OTT#";
	const PROCESS_INFO_PREFIX 				= "PI#";
	const MULTIPLAY_SELECT_SERVER_COUNTER 	= "MP_SS_CNTR";
	const MULTIPLAY_TOWN_TRANSACTION_PREFIX = "town_trans_";
	const MULTIPLAY_TOWN_ENTRY_PREFIX       = "town_entry_";

	protected $_inst = null;
	protected $_hosts = null;
	protected $_host = null;
	
	public function __construct(int $type=0)
	{
		$app = App::getInstance();
		$this->_hosts = $app->getContainer()['settings']['redis.hosts'];
		$this->_inst = new \Redis();
		$this->_host = $this->_hosts[$type];
		$this->_inst->pconnect($this->_host['ip'], $this->_host['port']);
	}

	public function getConnection()
	{
		return $this->_inst;
	}

}