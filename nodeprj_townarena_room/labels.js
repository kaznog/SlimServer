module.exports = {
	VERSION                    : 10200,		// バージョン
	
	SYNC_TYPE_ALWAYS           : 1,			// 常時broadcast型同期
	SYNC_TYPE_MS               : 2,			// Master/Slave同期
	SYNC_TYPE_TIMER            : 3,			// 間隔型同期
	SYNC_TYPE_AGGREAGATE       : 4,			// 集約型
	
	SYNC_MSG_CONNECTED         : 'connected',
	SYNC_MSG_LOGIN             : 'login',
	SYNC_MSG_CREATE            : 'create',
	SYNC_MSG_JOIN              : 'join',
	SYNC_MSG_LEAVE             : 'leave',
	SYNC_MSG_LOBBY             : 'lobby',
	SYNC_MSG_EXIT              : 'exit',
	SYNC_MSG_ALWAYS            : 'always',
	SYNC_MSG_AGGREGATE         : 'aggregate',
	SYNC_MSG_SEND_MASTER       : 'send_m',
	SYNC_MSG_BROADCAST         : 'sendall',
	SYNC_MSG_SEND              : 'msg',
	SYNC_MSG_SEND_ALL          : 'msgall',
	SYNC_MSG_CHAT              : 'chat',
	SYNC_MSG_REQUEST_MATCH     : 'req_match',
	SYNC_MSG_RESPONSE_MATCH    : 'res_match',
	SYNC_MSG_GIVEUP            : 'giveup',
	
	SYNC_MSG_SEARCH            : 'search',        // PVP系処理で追加
	SYNC_MSG_SEARCH_WAIT       : 'search_wait',   // PVP系処理で追加
	SYNC_MSG_MATCH             : 'match',         // PVP系処理で追加
	SYNC_MSG_MATCH_RESULT      : 'match_result',  // PVP系処理で追加
	SYNC_MSG_WAIT_CANCEL       : 'wait_cancel',   // PVP系処理で追加
	SYNC_MSG_BATTLE_READY      : 'battle_ready',  // PVP系処理で追加
	SYNC_MSG_BATTLE_RESULT     : 'battle_result', // PVP系処理で追加
	
	SYNC_MSG_PROCESS_STOP      : 'process_stop',
	SYNC_MSG_PROCESS_START     : 'process_start',
	SYNC_MSG_PROCESS_EXIT      : 'process_exit',
	SYNC_MSG_SYSTEM            : 'syscall',
	SYNC_MSG_SETSTATE          : 'setstate',
	SYNC_MSG_PLAYER_DISCONNECT : 'player_kill',

	SYNC_MSG_ENTRY_TOWN        : 'entry_town',
	SYNC_MSG_ENTRY_ARENA       : 'entry_arena',
	SYNC_MSG_LEAVE_ARENA       : 'leave_arena',
	SYNC_MSG_ROOM_INFO         : 'room_info',
	SYNC_MSG_MEMBER_EXIT       : 'member_exit',
	SYNC_MSG_CHANGE_MASTER     : 'change_master',
	SYNC_MSG_UNREGISTER_GAMEOBJECT : 'unregister_gameobject',
	SYNC_MSG_SYNCHRONIZEDATA   : 'synchronizedata',
	SYNC_MSG_IGNORE_MASTER     : 'ignore_master',
	ROOM_TYPE_TOWN             : 0,
	ROOM_TYPE_ARENA            : 1,
	ROOM_TYPE_PVP              : 2,
	TOWN_ENTRY_MAX             : 1000,
	ARENA_ENTRY_MAX            : 4,
	PVP_ENTRY_MAX              : 6,
	PVP_SIDE_MAX               : 3,

	SYNCHRONIZE_SAVEDATA_PREFIX_TOWN : 'saved_data_town_',
	SYNCHRONIZE_SAVEDATA_PREFIX_ARENA: 'saved_data_arena_',
	
	// const.xlsに設定されているものと同値
	SYNC_RES_OK                : 10000,
	SYNC_RES_NG                : 10001,
	SYNC_RES_CONNECT_ERROR     : 10002,
	SYNC_RES_ROOM_NOT_FOUND    : 11001,
	SYNC_RES_ROOM_FULL         : 11002,
	SYNC_RES_ROOM_ALREADY      : 11003,
	SYNC_RES_ROOM_ENTRY_TIMEOUT : 11005,
	SYNC_RES_CANCEL            : 12000,
	SYNC_RES_TIMEOUT           : 12001,
	SYNC_RES_STARTED           : 12002,
	
	SYNC_SYSCALL_EXIT             : 1,
	SYNC_SYSCALL_MAINTENANCE_EXIT : 2,
	SYNC_SYSCALL_INTERVAL_EXIT    : 3,
	
	COOKIE_CONNECTION_TOKEN  : 'rtidx',
	COOKIE_CONNECTION_PREFIX : 'OTT#',
	MULTIPLAY_SELECT_SERVER_COUNTER : 'MP_SS_CNTR',
//	COOKIE_CONNECTION_PPM_PREFIX : 'PPM#',
//	COOKIE_CONNECTION_PPR_PREFIX : 'PPR#',
	MULTIPLAY_TOWN_ENTRY_PREFIX : "town_entry_",
	
	CRYPT_SYS_KEY            : 3723,
	
	REDIS_EXPIRE_TIME        : 3600,
	REDIS_MAINTENANCE_KEY    : 'mschedule#marron',
	REDIS_PROCESS_INFO_PREFIX : 'PI#',
	
	MAX_PLAYER_NAME          : 32,
	
	REDIS_TOTAL_COUNT        : 'total',
	
	TBL_ROOM                 : 't_room',
	TBL_PVP_MATCHING         : 't_pvp_matching',
	TBL_PVP_MATCH_INFO       : 't_pvp_match_info',
	TBL_PVP_ROOM             : 't_pvp_room',
	TBL_PVP_DATA             : 't_pvp_data',
	TBL_PVP_MONS             : 't_pvp_mons',
	TBL_PVP_MEMBER           : 't_pvp_member',
	
	BLANK_TIMEOUT            : 600000,          // ping以外の通信が発生しない場合のtimeout時間 10分
	// BLANK_TIMEOUT            : 60000,          // ping以外の通信が発生しない場合のtimeout時間 1分
	ROOM_METHOD_TIMEOUT      : 30000,
	
	SOCKET_PING_TIMEOUT      : 15000,           // setState(false)時のpingTimeout時間 15秒
	SOCKET_PING_INTERVAL     : 7000,
	SOCKET_SUSPEND_TIMEOUT   : 180000,          // setState(true)時のpingTimeout時間 3分
	
	UNABLE_CON_TOTAL_COUNT   : 30,
	UE_COUNT_REBOOT          : 20,
	
	COOKIE_PASSCODE          : '_PASSCODE_',
	COOKIE_PLAYER_ID         : 'playerId',
	
	RT_JOIN_MODE_CREATE      : 1, // BITで使用
	RT_JOIN_MODE_JOIN        : 2, // BITで使用
	
	// RealtimeServerの定義と一緒
	MAINTENANCE_TYPE_ALL     : 0,
	MAINTENANCE_TYPE_MULTI   : 1,
	MAINTENANCE_TYPE_PVP     : 2,
	
	// const.xlsに設定されているものと同値
	RT_SERVER_MULTI          : 0,
	RT_SERVER_PVP_MATCHING   : 1,
	RT_SERVER_PVP_BATTLE     : 2,
	
	PVP_MATCH_SEARCH         : 1,
	PVP_MATCH_RANDOM         : 2,
	PVP_MATCH_RATE           : 4,
	
	PVP_RATE_MIN             : 0,
	PVP_RATE_MAX             : 99999,
	
	// const.xlsに設定されているものと同値
	ROOM_STATE_IDLE          : 0,
	ROOM_STATE_READY         : 1,
	ROOM_STATE_CLOSE         : 2,
	ROOM_STATE_SEARCH_WAIT   : 100,
	ROOM_STATE_MATCHING_WAIT : 101,
	ROOM_STATE_RESPONSE_WAIT : 102,
	ROOM_STATE_PARADE        : 201,
	ROOM_STATE_RESULT_FIXED  : 202,
	ROOM_STATE_NO_CONTEST    : 203,
	
	MEMBER_STATE_IDLE        : 0,
	MEMBER_STATE_JOINED      : 1,
	MEMBER_STATE_LEAVED      : 2,
	MEMBER_STATE_READY       : 3,
	MEMBER_STATE_GIVEUP      : 4,
	
	// マッチング有効期間系
	MATCH_ROOM_EXPIRE        : 3600,  // 60 min.
	PVP_ROOM_EXPIRE          : 7200,  // 120 min.
	PVP_ROOM_RESULT_EXPIRE   : 86400, // 24 hours.
	
	MATCHING_RATE_TIMEOUT    : 5000,  // 5 sec.
	MATCHING_TIMEOUT         : 30000, // 30 sec.
	RESPONSE_TIMEOUT         : 5000,  // 5 sec.

	// API ENDPOINTS
	API_ENDPOINT_GET_NATIVE_TOKEN                          : '/api/player/get_native_token',
	API_ENDPOINT_UPDATE_SESS                               : '/api/player/update_sess',
	API_ENDPOINT_LOGIN_BRIDGE                              : '/api/player/login_bridge',
	API_ENDPOINT_SIGNUP                                    : '/api/player/signup',
	API_ENDPOINT_LOGIN                                     : '/api/player/login',
	API_ENDPOINT_MULTIPLAY_GET_TOKEN                       : '/api/multiplay/get_token',
	API_ENDPOINT_MULTIPLAY_GET_TOWNLIST                    : '/api/multiplay/get_town_list',
	API_ENDPOINT_MULTIPLAY_ENTRY_TOWN                      : '/api/multiplay/entry_town',
	// API RESULT CODE
	API_RESULT_CODE_SUCCESS                                : 0,
	API_RESULT_CODE_OUTDATED                               : 1,
	API_RESULT_CODE_APPLYING                               : 2,
	API_RESULT_CODE_INVALID_REQUEST_HASH                   : 101,
	API_RESULT_CODE_INVALID_PARAMETERS                     : 102,
	API_RESULT_CODE_INSUFFICIENT_PARAMETERS                : 103,
	API_RESULT_CODE_INVALID_JSON_SCHEMA                    : 104,
	API_RESULT_CODE_DB_ERROR                               : 105,
	API_RESULT_CODE_DB_SHARDING_ERROR                      : 106,
	API_RESULT_CODE_NG_WORD                                : 107,
	API_RESULT_CODE_TRANSACTION_NOT_FOUND                  : 108,
	API_RESULT_CODE_TRANSACTION_ALREADY_COMMITED           : 109,
	API_RESULT_CODE_MEMCACHED_SET_ERROR                    : 110,
	API_RESULT_CODE_REDIS_SET_ERROR                        : 120,
    API_RESULT_CODE_REQUEST_RETRY                          : 199,

    //==========================================================
    // SQUARE ENIX BRIDGE
    //==========================================================
    // 通信時エラー
    API_RESULT_CODE_SQEXBRIDGE_FAILURE_SESSION             : 201,
    API_RESULT_CODE_SQEXBRIDGE_FAILURE_NATIVETOKEN_CREATE  : 202,
    API_RESULT_CODE_SQEXBRIDGE_FAILURE_SESSION_UPDATE      : 203,
    API_RESULT_CODE_SQEXBRIDGE_FAILURE_PEOPLE_CREATE       : 204,
    API_RESULT_CODE_SQEXBRIDGE_FAILURE_PEOPLE_LOGIN_CREATE : 205,
    API_RESULT_CODE_SQEXBRIDGE_FAILURE_PEOPLE_GET          : 206,
    API_RESULT_CODE_SQEXBRIDGE_FAILURE_GAME_WORLD_CREATE   : 207,
    API_RESULT_CODE_SQEXBRIDGE_FAILURE_GAME_WORLD_GET      : 208,

    //==========================================================
    // プレイヤー
    //==========================================================
    // プレイヤーが既に存在する.
    API_RESULT_CODE_PLAYER_ALREADY_EXISTS                  : 301,
    // プレイヤーが存在しない (存在しないUserIdでログインしようとした場合など).
    API_RESULT_CODE_PLAYER_NOT_FOUND                       : 302,
    // アクセストークンが不正
    API_RESULT_CODE_INVALID_ACCESS_TOKEN                   : 303,
    // ログイン方法が不正
    API_RESULT_CODE_INVALID_PLAYER_IDENTITY_KIND           : 304,
    // ログイン必須.
    API_RESULT_CODE_LOGIN_REQUIRED                         : 305,

    //==========================================================
    // Multi Server
    //==========================================================
    API_RESULT_CODE_MULTI_SERVER_STATE_MAINTENANCE         : 400,
    API_RESULT_CODE_MULTI_SERVER_TOWN_ENTRY_FULL           : 500,
    API_RESULT_CODE_TOWN_NOT_EXIST                         : 501,

    // 未知のエラー.
    API_RESULT_CODE_UNKNOWN_ERROR                          : 999
};
