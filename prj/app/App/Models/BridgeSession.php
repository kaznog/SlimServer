<?php
namespace App\Models;

use \App\Services\CacheService;
use \App\Models\BridgeSessionObj;
use \App\App;

class BridgeSession
{
	const SESSION_DEVICE_PLATFORM       = 'sb_device';
	const SESSION_USER_ID		        = 'sb_userId';
	const SESSION_WORLD_ID		        = 'sb_worldId';
	const SESSION_BRIDGE_NSID 			= 'sb_nsid';
	const SESSION_SHARED_SECURITY_KEY	= 'sb_sskey';
	const SESSION_BRIDGE_UID			= 'sb_uid';					// user id (bridge user id)
	const SESSION_REGIST		        = 'sb_regist';				// 初回ログインかどうか
	const SESSION_SQEX_ID		        = 'sb_sqex_id';				// SQEXID
	const SESSION_TAGNAME		        = 'sb_tagName';				// タグ名
	const SESSION_NATIVE_TAGNAME        = 'sb_nativeTag';			// nativeタグ名

	protected $key;
	protected $sessionObj;
	public function __construct(string $user_id, BridgeSessionObj $sessionObj = null)
	{
		$this->key = CacheService::getBridgeSessionObjKey($user_id);
		if (is_null($sessionObj)) {
			$sessionObj = CacheService::get(
				$this->key,
				function ($user_id) {
					$bsObj = new BridgeSessionObj();
					return $bsObj;
				}
			);
		}
		$this->sessionObj = $sessionObj;
	}

	public static function clear()
	{
		unset($_SESSION[self::SESSION_DEVICE_PLATFORM]);
		unset($_SESSION[self::SESSION_USER_ID]);
		unset($_SESSION[self::SESSION_WORLD_ID]);
		unset($_SESSION[self::SESSION_BRIDGE_NSID]);
		unset($_SESSION[self::SESSION_SHARED_SECURITY_KEY]);
		unset($_SESSION[self::SESSION_BRIDGE_UID]);
		unset($_SESSION[self::SESSION_REGIST]);
	}

	// ユーザ情報のキャッシュ
	public function setUserProfile($code)
	{
		$sessionObj = $this->sessionObj;
		$sessionObj->sqex_id		= isset($code['sqexId']) ? strval($code['sqexId']) : false;
		$sessionObj->bridge_user_id = isset($code['userId']) ? strval($code['userId']) : false;
		$sessionObj->tag_name       = isset($code['tagName']) ? strval($code['tagName']) : false;
		$sessionObj->native_tag_name= isset($code['nativeTagName']) ? strval($code['nativeTagName']) : false;
		$this->updateSessionObj();
	}

	public function getDevicePlatform()
	{
		return $this->sessionObj->device_platform;
	}

	public function setDevicePlatform(int $platform)
	{
		$this->sessionObj->device_platform = $platform;
		$this->updateSessionObj();
	}

	public function getWorldId()
	{
		return $this->sessionObj->world_id;
	}

	public function setWorldId($world_id)
	{
		$this->sessionObj->world_id = $world_id;
		$this->updateSessionObj();
	}

	public function getNativeSessionId()
	{
		return $this->sessionObj->native_session_id;
	}

	public function setNativeSessionId($native_session_id)
	{
		$this->sessionObj->native_session_id = $native_session_id;
		$this->updateSessionObj();
	}

	public function getSharedSecurityKey()
	{
		return $this->sessionObj->shared_secrity_key;
	}

	public function setSharedSecurityKey($shared_secrity_key)
	{
		$this->sessionObj->shared_secrity_key = $shared_secrity_key;
		$this->updateSessionObj();
	}

	public function getBridgeUserId()
	{
		return $this->sessionObj->bridge_user_id;
	}

	public function setBridgeUserId($bridge_user_id)
	{
		$this->sessionObj->bridge_user_id = $bridge_user_id;
		$this->updateSessionObj();
	}

	public function getRegist()
	{
		return $this->sessionObj->regist;
	}

	public function setRegist($regist)
	{
		$this->sessionObj->regist = $regist;
		$this->updateSessionObj();
	}

	public function getSqexId()
	{
		return $this->sessionObj->sqex_id;
	}

	public function setSqexId($sqex_id)
	{
		$this->sessionObj->sqex_id = $sqex_id;
		$this->updateSessionObj();
	}

	public function getTagName()
	{
		return $this->sessionObj->tag_name;
	}

	public function setTagName($tag_name)
	{
		$this->sessionObj->tag_name = $tag_name;
		$this->updateSessionObj();
	}

	public function getNativeTagName()
	{
		return $this->sessionObj->native_tag_name;
	}

	public function setNativeTagName($native_tag_name)
	{
		$this->sessionObj->native_tag_name = $native_tag_name;
		$this->updateSessionObj();
	}

	public function updateSessionObj()
	{
		$app = App::getInstance();
		// replaceは危険なのでset
		$app->memcached->set($this->key, $this->sessionObj);
	}
}