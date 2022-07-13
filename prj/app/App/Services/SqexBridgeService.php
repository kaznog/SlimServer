<?php
namespace App\Services;

use \App\Models\Platform;
use \App\Models\ResultCode;
use \Utils\SqexBridgeRequest;

class SqexBridgeService
{
	// Bridgeセキュア通信の流れ

	// 1. セッショントークン生成に必要な認証トークンを作成(HTTP Method:POST)
	// name utility.nativetoken
	// @response nativeToken
	// クライアントsignup受付時にtokenを取得してクライアントへ返却する
	const URI_utility_nativetoken	= 'utility.nativetoken';

    // 2. クライアント側の処理。nativeSessionIdを生成。暗号化用の事前共有鍵となるsharedSecurityKeyもついてくる(HTTP Method:POST)
    // name session.create
    // @response nativaSessionId
    // @response sharedSecurityKey
    const URI_session_create        = 'session.create';

	// 3. クライアント=ゲームサーバ間で引き渡されたnativeSessionIdをupdateで再生成(HTTP Method:PUT)
	// name session.update
	// @response nativeSessionId
	// 以降、HTTP headerまたはmemcachedにPlayerIDに紐付けて保存して利用する
	const URI_session				= 'session';

	const URI_people				= 'people';
	const URI_people_login			= 'people.login';
	const URI_game_world			= 'game.world';
	const URI_game_serialcode		= 'game.serialcode';
	const URI_coin_payment			= 'coin.payment';
	const URI_coin_deposit			= 'coin.deposit';
	const URI_coin_deposit_game		= 'coin.deposit.game';
	const URI_coin_deposit_ios		= 'coin.deposit.ios';
	const URI_coin_deposit_android	= 'coin.deposit.android';
	const URI_coin_deposit_summary	= 'coin.deposit.summary';
	const URI_information			= 'information';
	const URI_information_detail	= 'information.detail';
	const URI_cesalimit				= 'cesalimit';

	protected $bridge = null;
	protected $device_type;
	protected $nativeSessionId = "";

	public function __construct(string $nativeSessionId = '', int $device_type = Platform::PLATFORM_IOS)
	{
		$this->bridge = new SqexBridgeRequest();
		$this->nativeSessionId = $nativeSessionId;
		$this->device_type = $device_type;
	}

	public function setNativeSessionId($nativeSessionId)
	{
		$this->nativeSessionId = $nativeSessionId;
	}

	// セッショントークン生成に必要な認証トークンを作成
	// 新規ユーザー登録時にsignupの戻りとして利用する
	public function utility_nativetoken_create()
	{
		$res = $this->bridge->create(
				self::URI_utility_nativetoken,
				[],
				[],
				[]
				);
		$this->result_code = $res['statusCode'];
		$error = $this->result_code != 201 ? true : false;
		return [
				'res' => ( $error ? ResultCode::SQEXBRIDGE_FAILURE_NATIVETOKEN_CREATE : ResultCode::SUCCESS ),
				'ret' => $res['responseBody'],
				'resCode' => $this->result_code,
				'errorCode' => ( $error ) ? substr($res['responseBody'],1,5) : '',
				];
	}

	//　ネイティブセッションのネイティブセッションIDを変更
	public function session_update($nativeSessionId)
	{
		$res = $this->bridge->set(
				self::URI_session,
				[$nativeSessionId],
				[],
				[]
				);
		$this->result_code = $res['statusCode'];
		$error = $this->result_code != 202 ? true : false;
		return [
				'res' => ( $error ? ResultCode::SQEXBRIDGE_FAILURE_SESSION_UPDATE : ResultCode::SUCCESS ),
				'ret' => $res['responseBody'],
				'resCode' => $this->result_code,
				'errorCode' => ( $error ) ? substr($res['responseBody'],1,5) : '',
			    ];
	}

	//　ユーザーデータの本作成
	public function people_create($inviteCode = null, $shortInviteCode = null, $guessInviteCode = null)
	{
		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '?.?.?.?';
		$requestbody = [
			'UUID' => '@self',
			'gameId' => '@self',
			'deviceType' => $this->device_type,
			'createIpAddress' => $ip
		];
		if ( !is_null($inviteCode) ) $requestbody['inviteCode'] = $inviteCode;
		if ( !is_null($shortInviteCode) ) $requestbody['shortInviteCode'] = $shortInviteCode;
		if ( !is_null($guessInviteCode) ) $requestbody['guessInviteCode'] = $guessInviteCode;
		$res = $this->bridge->create(
				self::URI_people,
				[],
				['xoauth_native_session_id' => $this->nativeSessionId],
				$requestbody
				);
		$this->result_code = $res['statusCode'];
		$error = $this->result_code != 201 ? true : false;
		return [
			'res' => ( $error ? ResultCode::SQEXBRIDGE_FAILURE_PEOPLE_CREATE : ResultCode::SUCCESS ),
			'ret' => $res['responseBody'],
			'resCode' => $this->result_code,
			'errorCode' => ( $error ) ? substr($res['responseBody'],1,5) : '',
		];
	}

	//　ユーザーのプロフィール情報を取得
	public function people_get($getBridgeProfile = false, $getBridgeExtProfile = false,  $userId = '@self')
	{
		$res = $this->bridge->get(
				self::URI_people,
				[$userId],
				[//'xoauth_requestor_id' => $UUID, // 原則、nativeSessionIdで管理するためにサーバ内ではUUIDに触れない
					'xoauth_device_type'       => $this->device_type,
					'xoauth_native_session_id' => $this->nativeSessionId,
					'getMoneyField'		       => 1,		// チャージ情報
					'getBridgeProfile'	       => ($getBridgeProfile ? 1 : 0),
					'getBridgeExtProfile'	   => 1
				]
		);
		$this->result_code = $res['statusCode'];
		$error = $this->result_code != 200 ? true : false;
		return array(
				'res' => ( $error ? ResultCode::SQEXBRIDGE_FAILURE_PEOPLE_GET : ResultCode::SUCCESS ),
				'ret' => $res['responseBody'],
				'resCode' => $this->result_code,
				'errorCode' => ( $error ) ? substr($res['responseBody'],1,5) : '',
		);
	}

			//　ユーザのログイン情報作成
	public function people_login_create()
	{
		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '?.?.?.?';
		$res = $this->bridge->create(
				self::URI_people_login,
				array(),
				array(//'xoauth_requestor_id' => $UUID, // 原則、nativeSessionIdで管理するためにサーバ内ではUUIDに触れない
						'xoauth_device_type' => $this->device_type,
						'xoauth_native_session_id' => $this->nativeSessionId),
				array('UUID' => '@self', // 原則、nativeSessionIdで管理するためにサーバ内ではUUIDに触れない
						'gameId' => '@self',
						'deviceType' => '@self',
						'loginIpAddress' => $ip)
				);
		$this->result_code = $res['statusCode'];
		$error = $this->result_code != 201 ? true : false;
		return array(
				'res' => ( $error ? ResultCode::SQEXBRIDGE_FAILURE_PEOPLE_LOGIN_CREATE : ResultCode::SUCCESS ),
				'ret' => $res['responseBody'],
				'resCode' => $this->result_code,
				'errorCode' => ( $error ) ? substr($res['responseBody'],1,5) : '',
		);
	}

	//　ユーザーのワールド登録情報を取得
	public function game_world_get()
	{
		$res = $this->bridge->get(
				self::URI_game_world,
				array('@self'),
				array(//'xoauth_requestor_id' => $UUID, // 原則、nativeSessionIdで管理するためにサーバ内ではUUIDに触れない
						'xoauth_device_type' => $this->device_type,
						'xoauth_native_session_id' => $this->nativeSessionId)
				);
		$this->result_code = $res['statusCode'];
		$error = $this->result_code != 200 ? true : false;
		return array(
				'res' => ( $error ? ResultCode::SQEXBRIDGE_FAILURE_GAME_WORLD_GET : ResultCode::SUCCESS ),
				'ret' => $res['responseBody'],
				'resCode' => $this->result_code,
				'errorCode' => ( $error ) ? substr($res['responseBody'],1,5) : '',
		);
	}

	//　ユーザーのワールド登録情報を作成
	public function game_world_create()
	{
		$res = $this->bridge->create(
				self::URI_game_world,
				array('@self'),
				array(//'xoauth_requestor_id' => $UUID, // 原則、nativeSessionIdで管理するためにサーバ内ではUUIDに触れない
						'xoauth_device_type' => $this->device_type,
						'xoauth_native_session_id' => $this->nativeSessionId),
				array('worldId' => '@self')
				);
		$this->result_code = $res['statusCode'];
		$error = $this->result_code != 201 ? true : false;
		return array(
				'res' => ( $error ? ResultCode::SQEXBRIDGE_FAILURE_GAME_WORLD_CREATE : ResultCode::SUCCESS ),
				'ret' => $res['responseBody'],
				'resCode' => $this->result_code,
				'errorCode' => ( $error ) ? substr($res['responseBody'],1,5) : '',
				);
	}

}