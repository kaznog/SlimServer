<?php
use \Middlewares\UseMonolog;
use \Utils\JsonLineFormatter;
return [
	'settings' => [
        'https' => false,
        'log.web' => [
			'default'   => [UseMonolog::createStreamHandler('/var/www/shared/log/app.json.log', \Psr\Log\LogLevel::DEBUG, true, new JsonLineFormatter())],
			'admin'     => [UseMonolog::createStreamHandler('/var/www/shared/log/admin.log', \Psr\Log\LogLevel::DEBUG)],
        ],
        'log.batch' => [
			'default'   => [UseMonolog::createStreamHandler('/var/www/shared/log/app.batch.log', \Psr\Log\LogLevel::DEBUG)],
        ],
		'debug' => true,
		'SQEX_GRIDGE' => [
			'API_SERVER_URL_BASE'        => 'http://test.restapi.hoge.jp/api/native/v1',
			'HTML_NATIVE_SESSION_CREATE' => 'https://test.psg.hoge.jp/native/session',
			'GAME_ID'                    => 999,
			'WORLD_ID'                   => 001,
			'CONSUMER_KEY'               => 'hogehoge_consumer_key',
			'CONSUMER_SECRET'            => 'hogehoge_cinsumer_secret',
			'COIN_NAME'                  => 'ジェム',
			'COIN_FREE_NAME'             => '無料ジェム',
			'COIN_GAME_NAME'             => 'ゲーム内付与ジェム',
			'BRIDGE_CONNECTION_TIMEOUT'  => 15,
		],
		'templates.path' => APP_ROOT . '/app/App/Templates',
		'csv.path' => '/tmp/csv/',
		'backup_uploaded.path' => '/tmp/backup_uploaded/',
		'requesthash.secret' => 'thisisrequesthashforlocal',
		'requesthash.check' => true,
		'sessionkey.prefix' => 'app_sess_',
		'sqexbridge.sess.prefix' => 'app_sqexbdirge_sess_',
		'memcached.hosts' => [
		    ['localhost', 11211, 1]
		],
		'memcached.prefix' => 'app_local_',
		'nodejs.hosts' => [
			['host' => 'cent7php7proto', 'id' => 'cent7php7proto_0', 'url' => 'ws://192.168.2.92/sio0/?EIO=3&transport=websocket'],
			['host' => 'cent7php7proto', 'id' => 'cent7php7proto_1', 'url' => 'ws://192.168.2.92/sio1/?EIO=3&transport=websocket'],
		],
		'redis.hosts' => [
			['name' => 'KVS_MASTER',    'ip' => '192.168.2.92', 'port' => '6379'],
			['name' => 'KVS_SLAVE',     'ip' => '192.168.2.92', 'port' => '6379'],
			['name' => 'PUBSUB_MASTER', 'ip' => '192.168.2.92', 'port' => '6379'],
			['name' => 'PUBSUB_SLAVE',  'ip' => '192.168.2.92', 'port' => '6379'],
		],
		'ampq.host' => ['localhost', 5672, 'app', 'hichewL0chew'],
		'db.connections' => [
		    'shard_000' => [
		        'master' => ['username' => 'app', 'password' => 'hichewL0chew', 'connection_string' => "mysql:host=localhost;dbname=app_shard_000;charset=utf8"],
		        'replicas' => [
		            ['username' => 'app', 'password' => 'hichewL0chew', 'connection_string' => "mysql:host=localhost;dbname=app_shard_000;charset=utf8"],
		        ],
		    ],
		    'shard_001' => [
		        'master' => ['username' => 'app', 'password' => 'hichewL0chew', 'connection_string' => "mysql:host=localhost;dbname=app_shard_001;charset=utf8"],
		        'replicas' => [
		            ['username' => 'app', 'password' => 'hichewL0chew', 'connection_string' => "mysql:host=localhost;dbname=app_shard_001;charset=utf8"],
		        ],
		    ],
		    'shard_002' => [
		        'master' => ['username' => 'app', 'password' => 'hichewL0chew', 'connection_string' => "mysql:host=localhost;dbname=app_shard_002;charset=utf8"],
		        'replicas' => [
		            ['username' => 'app', 'password' => 'hichewL0chew', 'connection_string' => "mysql:host=localhost;dbname=app_shard_002;charset=utf8"],
		        ],
		    ],
		    'shard_003' => [
		        'master' => ['username' => 'app', 'password' => 'hichewL0chew', 'connection_string' => "mysql:host=localhost;dbname=app_shard_003;charset=utf8"],
		        'replicas' => [
		            ['username' => 'app', 'password' => 'hichewL0chew', 'connection_string' => "mysql:host=localhost;dbname=app_shard_003;charset=utf8"],
		        ],
		    ],
		    'shard_battle' => [
		        'master' => ['username' => 'app', 'password' => 'hichewL0chew', 'connection_string' => "mysql:host=localhost;dbname=app_common;charset=utf8"],
		        'replicas' => [
		            ['username' => 'app', 'password' => 'hichewL0chew', 'connection_string' => "mysql:host=localhost;dbname=app_common;charset=utf8"],
		        ],
		    ],
		    'shard_common' => [
		        'master' => ['username' => 'app', 'password' => 'hichewL0chew', 'connection_string' => "mysql:host=localhost;dbname=app_common;charset=utf8"],
		        'replicas' => [
		            ['username' => 'app', 'password' => 'hichewL0chew', 'connection_string' => "mysql:host=localhost;dbname=app_common;charset=utf8"],
		        ],
		    ],
		],
		'db.clusters' => [
		    'master_data' => 'shard_common', // シャーディングしない場合は string でシャード名を指定.
		    'players_identity' => 'shard_common',
		    'battle' => 'shard_battle',
		    'player' => ['shard_000', 'shard_001', 'shard_002', 'shard_003'], // シャーディングする場合はシャード名の配列を指定.
		],
		'admin.users' => [
		    'account.path' => '/var/www/shared/cnf/admin_users.json',
		    'roles.path' => '/db/admin_users_roles.db',
		    'roles_dst.path' => '/var/www/shared/cnf/admin_users_roles.db',
		    'log.path' => '/var/www/shared/log/admin_user_log.db',
		],
	],
];
?>