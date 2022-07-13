global.__defineGetter__('__LINE__', function () { return (new Error()).stack.split('\n')[2].split(':').reverse()[1]; });
global.__defineGetter__('__FILE__', function () { return (new Error()).stack.split('\n')[2].match(/\/([^/:]*?):/)[1]; });
var os			 = require('os');
var hostname	 = os.hostname();
var http 		 = require("http");
var fs			 = require("fs");
var msgpack      = require("msgpack");
var workerId	 = parseInt(process.env.NODE_APP_INSTANCE || 0);
var hostId		 = hostname + '_' + workerId;
var port		 = 3030 + workerId;
console.log("host["+hostname+"] port["+port+"]");
var	currentDate  = (new Date().getTime());
var setting      = require('./conf/settings.'+hostname+'.js');
setting.hostname = hostname;
setting.port     = port;
setting.pid      = process.pid;
var label		 = require('./labels.js');
var amqp         = require('amqplib/callback_api');
var Synchronizer = require('./synchronizer');
var synchronizer = {};
synchronizer['Town']  = {};
synchronizer['Arena'] = {};
var roomIds = [];

var io			= require('socket.io')();
// オブジェクトファクトリー
var factory		= require('./factory.js')();

var sioPath		= setting.socket_io.path;
// path
if ( setting.socket_io.path ) {
  if ( setting.socket_io.multi_path ) {
    sioPath = setting.socket_io.path + (workerId + parseInt(setting.socket_io.path_offset));
  }
  io.path(sioPath);
}

var EventEmitter= require('events').EventEmitter,
domain			= require('domain').create(),
cookieParser	= require('socket.io-cookie'),
redis			= require('redis'),
redisAdapter	= require("socket.io-redis"),
redisKVS	    = redis.createClient(setting.redis.master.port, setting.redis.master.host),
redisPub		= redis.createClient(setting.redis.pub.port,    setting.redis.pub.host),
redisSub		= redis.createClient(setting.redis.sub.port,    setting.redis.sub.host),
redisWatcher	= require('./redis-watcher.js');

io.adapter(redisAdapter({ pubClient: redisPub, subClient: redisSub }));

var redisEvPub	= redis.createClient(setting.redis.pub.port, setting.redis.pub.host, {return_buffers:true}); 	// 発信用
var redisEvSub	= redis.createClient(setting.redis.sub.port, setting.redis.sub.host, {return_buffers:true});	// 受信用
var channels = [
	label.SYNC_MSG_PROCESS_STOP,
	label.SYNC_MSG_PROCESS_START,
	label.SYNC_MSG_PROCESS_EXIT,
	label.SYNC_MSG_PLAYER_DISCONNECT,
	// label.SYNC_MSG_CHANGE_MASTER,
	// label.SYNC_MSG_REQUEST_MATCH,
	// label.SYNC_MSG_RESPONSE_MATCH,
	label.SYNC_MSG_SYNCHRONIZEDATA,
	label.SYNC_MSG_CHAT
];
// 受信受付登録
redisEvSub.subscribe.apply(redisEvSub, channels);
var ev = new EventEmitter;
// 受信処理
redisEvSub.on('message', function(channel, message) {
	// console.log("["+ channel + "]");
	// event emit
	ev.emit(channel, message);
});

ev.on(label.SYNC_MSG_SYNCHRONIZEDATA, function(recv) {
	var obj = JSON.parse(recv);
	if (typeof(obj.hostId) == "undefined") {
		return;
	}
	if (obj.hostId == hostId) {
		return;
	}
	// console.log('host['+hostId+"] event subscribe [" + label.SYNC_MSG_SYNCHRONIZEDATA + "]:" + recv);
	// ばりばり共有
	if (obj.arenaId != '') {
		if (! (obj.arenaId in synchronizer['Arena']) ) {
			synchronizer['Arena'][obj.arenaId] = new Synchronizer('./'+label.SYNCHRONIZE_SAVEDATA_PREFIX_TOWN+hostId+obj.arenaId+'.txt');
		}
		synchronizer['Arena'][obj.arenaId].add(obj.data);
	} else if (obj.townId != '') {
		if (! (obj.townId in synchronizer['Town']) ) {
			synchronizer['Town'][obj.townId] = new Synchronizer('./'+label.SYNCHRONIZE_SAVEDATA_PREFIX_TOWN+hostId+obj.townId+'.txt');		
		}
		synchronizer['Town'][obj.townId].add(obj.data);
	}
});

ev.on(label.SYNC_MSG_CHAT, function(recv) {
	var obj = JSON.parse(recv);
	if (obj.hostId == hostId) {
		return;
	}
	var roomId = '';
	var roomType = label.ROOM_TYPE_TOWN;
	if (obj.arenaId != '' && (obj.arenaId in synchronizer['Arena'])) {
		roomId = obj.arenaId;
		roomType = label.ROOM_TYPE_ARENA;
	} else if (obj.townId != '' && (obj.townId in synchronizer['Town'])) {
		roomId = obj.townId;
		roomType = label.ROOM_TYPE_TOWN;
	} else {
		return;
	}
	root.to(roomId).json.emit(label.SYNC_MSG_CHAT, {resultCode: label.SYNC_RES_OK, roomType: roomType, message: recv.message});
});

// プレイヤー切断
ev.on(label.SYNC_MSG_PLAYER_DISCONNECT, function(message) {
});

// メンテナンスIN
ev.on(label.SYNC_MSG_PROCESS_STOP, function(message) {
});

// メンテナンスOUT
ev.on(label.SYNC_MSG_PROCESS_START, function(message) {
});

// プロセス終了
ev.on(label.SYNC_MSG_PROCESS_EXIT, function(message) {
});

// redis master pool
var poolRedisMaster = redisWatcher({
	'host': setting.redis.master.host,
	'password': setting.redis.master.password,
	'maxConnections': setting.redis.master.maxConnections
});

// redis slave pool
var poolRedisSlave = redisWatcher({
	'host': setting.redis.slave.host,
	'password': setting.redis.slave.password,
	'maxConnections': setting.redis.slave.maxConnections
});

var processInfo = factory.createProcessInfoObject();
processInfo.version = label.VERSION;
processInfo.hostId  = hostId;
processInfo.working = true;

processInfo.aSockNum[label.ROOM_TYPE_TOWN] = 0;
processInfo.aSockNum[label.ROOM_TYPE_ARENA] = 0;
processInfo.aSockNum[label.ROOM_TYPE_PVP] = 0;

processInfo.aRoomNum[label.ROOM_TYPE_TOWN] = 0;
processInfo.aRoomNum[label.ROOM_TYPE_ARENA] = 0;
processInfo.aRoomNum[label.ROOM_TYPE_PVP] = 0;

// エラーハンドリング
process.on('uncaughtException', function(err) {
	// コンソールに出力
	console.error('uncaught exception '+err);
	processInfo.uncaught++;
});

domain.on('error', function (e) {
	console.error("domain:", e.message);
});

var room        = require('./room.js')(poolRedisMaster, poolRedisSlave, setting.room, setting.debug);

// 初回接続時処理
// 認証
io.use(cookieParser);
io.sockets.use(function(socket, next) {
	var clientIpAddress = socket.request.headers['x-forwarded-for'] || socket.request.connection.remoteAddress;
	if ( setting.debug )
		console.log("authorization ==================" + formatDate(new Date(), 'YYYY/MM/DD hh:mm:ss'));

	// 認証無
	if ( !setting.auth && (label.COOKIE_PLAYER_ID in socket.handshake.query)) {
		socket.player = factory.createPlayerObject();
		socket.player.remote = clientIpAddress;
		if ( socket.request.headers.cookie && label.COOKIE_PLAYER_ID in socket.request.headers.cookie ) {
			socket.player.playerId = socket.request.headers.cookie[label.COOKIE_PLAYER_ID];
   			socket.player.admin    = ( socket.marron.playerId == 'admin' );
		}
		// handshake.queryにplayerIdが設定されていたら、playerIdを上書きする。
		// 理由は不明
		if ( socket.handshake.query && label.COOKIE_PLAYER_ID in socket.handshake.query ) {
			socket.player.playerId = socket.handshake.query[label.COOKIE_PLAYER_ID];
		}
		next();
	}

	// 認証有
	// 認証する場合は、クッキーにあるトークンとPREFIX文字列をredisのkeyとして処理するので、トークンがない場合はエラー
	if ( !socket.request.headers.cookie[label.COOKIE_CONNECTION_TOKEN] ) {
		console.log('connection error');
		next(new Error('connection error'));
		return;
	}

	try {
		console.log(__FILE__+":"+__LINE__+"authorization check start");
		// OTT# + クッキー[rtidx]に設定されている文字列をキーとしてredisからjsonをしゅとくして playerObjectを作成し、socketに登録する
		var rtidx = label.COOKIE_CONNECTION_PREFIX + socket.request.headers.cookie[label.COOKIE_CONNECTION_TOKEN];
		poolRedisMaster.get().getClient(function (client, done) {
			client.get(rtidx, function (err, val) {
				if ( err || !val ) {
					done();
					if ( setting.debug )
						console.log(formatDate(new Date(), 'YYYY/MM/DD hh:mm:ss')+" "+__FILE__+":"+__LINE__+" connection fail(" + err + "): " + rtidx + " addr[" + clientIpAddress + "]");
					processInfo.connectionFailCount++;
					console.log('connection fail(1)');
					next(new Error('connection fail(1)'));
					return;
				}

				console.log(__FILE__+":"+__LINE__+" authorization redis token exists ok");
				// ユーザー情報を取得
				var data = JSON.parse(val);
				var timestamp = (data.date*1000) + data.msec;

				socket.player = factory.createPlayerObject();
				// socket.player.roomType		= data.roomType;
				socket.player.roomType		= label.ROOM_TYPE_TOWN;
				socket.player.playerId		= data.playerId;
				socket.player.level			= data.level;
				socket.player.bBeginner 	= data.bBeginner;
				socket.player.regulation	= data.regulation;
				socket.player.remote		= clientIpAddress;
				socket.player.admin			= ( socket.player.playerId == 'admin' );
				socket.player.blank         = false;
				socket.player.timerId       = false;
				// socket.player.synchronizeTimerId = false;
				socket.player.maxPlayers = label.TOWN_ENTRY_MAX;
				socket.player.timeStamp   = timestamp;

				if ( !socket.player.admin )
					// redisに登録されているplayerデータを削除
					client.del(rtidx);
				
				// callbackとしてdone()しているけど、ここの場合は無処理。ルール
				done();

				// 次のuseのためにnext()する。useを使用するうえでのルール
				next();
			});
		});
	} catch(ex) {
		console.log('no user addr[' + clientIpAddress + ']');
		next(new Error('connection error'));
		return;
	}
});

// synchronizer initialize
// setInterval(function() {
// 	synchronizer.saveGameObjects();
// 	console.log("synchronizer.saveGameObjects");
// }, 1000 * 60);
// synchronizer.loadGameObjects(function() {});

var root = io.on('connection', function (socket) {
	console.log('socket.on host['+hostId+'][connection] time['+formatDate(new Date(), 'YYYY/MM/DD hh:mm:ss')+'] playerId['+socket.player.playerId+']');

	socket.player.loginDate	 = currentDate;
	socket.player.lastAccess = currentDate;

	// total++
	processInfo.totalCount++;
	socket.json.emit(label.SYNC_MSG_CONNECTED, { id: socket.id, resultCode: label.SYNC_RES_OK });

	// login
	socket.on(label.SYNC_MSG_LOGIN, function (recv) {
		console.log('socket.on host['+hostId+'][login] time['+formatDate(new Date(), 'YYYY/MM/DD hh:mm:ss')+']');
		socket.json.emit(label.SYNC_MSG_LOGIN, { id: socket.id, resultCode: label.SYNC_RES_OK });
		socket.player.lastAccess = currentDate;
	});

	// Town入室
	socket.on(label.SYNC_MSG_ENTRY_TOWN, function (recv) {
		console.log('socket.on host['+hostId+'][entry_town] time['+formatDate(new Date(), 'YYYY/MM/DD hh:mm:ss')+']');
		var rtidx = label.MULTIPLAY_TOWN_ENTRY_PREFIX + socket.player.playerId;
		poolRedisMaster.get().getClient(function (client, done) {
			client.get(rtidx, function (err, val) {
				if ( err || !val ) {
					done();
					if ( setting.debug )
						console.log(formatDate(new Date(), 'YYYY/MM/DD hh:mm:ss')+" "+__FILE__+":"+__LINE__+" town entry fail: " + rtidx + " addr[" + clientIpAddress + "]");

					socket.json.emit(label.SYNC_MSG_ENTRY_TOWN, {id: socket.id, resultCode: label.SYNC_RES_ROOM_ENTRY_TIMEOUT});
					return;
				}
				if (! (recv.townId in synchronizer['Town']) ) {
					synchronizer['Town'][recv.townId] = new Synchronizer('./'+label.SYNCHRONIZE_SAVEDATA_PREFIX_TOWN+hostId+recv.townId+'.txt');
				}
				var isImmediatelyOwned = synchronizer['Town'][recv.townId].Length() === 0;

				socket.player.roomType = label.ROOM_TYPE_TOWN;
				// とりあえず、何も考えないでtownにjoin
				room.join(socket, recv.townId, function (res, roomId, roomInfo, createRoom) {
					if ( res == label.SYNC_RES_NG ) {
						console.log('err at '+__FILE__+':'+__LINE__);
						socket.disconnect();
						return;
					}
					// 自分のIDが無い
					var myId = -1;
					var isMaster = false;
					if ( res == label.SYNC_RES_OK ) {
						myId = room.getLid(roomInfo, socket.id);
						
						// 自分のIDが無い
						if ( myId == -1 ) {
							console.log('err at '+__FILE__+':'+__LINE__);
							socket.disconnect();
							return;
						}
						
						socket.player.townId   = roomId;
						socket.player.masterId = roomInfo.masterId;
						socket.player.masterNo = roomInfo.masterNo;
						isMaster = (socket.player.masterId == socket.id);
						console.log(createRoom ? 'create room:[' + roomId + ']' : 'join room:[' + roomId +']');

						if (createRoom == true) {
							roomIds.push(roomId);
							console.log("roomIds push:"+roomId);
							for(var i of roomIds) {
								console.log("roomIds roomId: " + i);
							}
						}
					}
					// 入室に成功したことを通知
					socket.json.emit(label.SYNC_MSG_ENTRY_TOWN, {id: socket.id, resultCode: res, townId: recv.townId, isMaster: isMaster});
					if ( res == label.SYNC_RES_OK )
					{
						// townにいるほかのキャラクター情報を送信
						root.sendSavedComponentsTo(socket, recv.townId, recv.arenaId, isImmediatelyOwned);
						// 全員へ送信
						root.to(recv.townId).json.emit(label.SYNC_MSG_ROOM_INFO, {id: socket.id, resultCode: label.SYNC_RES_OK, room: roomInfo});
					}
					socket.player.lastAccess = currentDate;
					amqp.connect(setting.amqp.url, function (err, conn) {
						if ( err ) {
							console.log("amqp connection error");
							return;
						}
						conn.createChannel(function (err, ch) {
							if ( err ) {
								console.log("amqp createChannel error");
								return;
							}
							var chname = 'unset_townentry_reserve';
							ch.assertQueue(chname, {durable: false});

							var obj = new Object();
							obj.playerId = socket.player.playerId;
							ch.sendToQueue(chname, new Buffer(JSON.stringify(obj)), {persistent: false}, function (err, ok) {
								if ( err ) {
									console.warn("Message nacked!");
								} else {
									console.log("Message acked");
								}
							});
						});
						setTimeout(function() {
							conn.close();
						}, 500);
					});
				});
			});
		});
	});

	// Arena入室
	// SYNC_MSG_ENTRY_ARENAにしてるけど、SYNC_MSG_ENTRY_BATTLE_FIELDになると思う
	socket.on(label.SYNC_MSG_ENTRY_ARENA, function (recv) {
		if (! (recv.arenaId in synchronizer['Arena']) ) {
			synchronizer['Arena'][recv.arenaId] = new Synchronizer('./'+label.SYNCHRONIZE_SAVEDATA_PREFIX_ARENA+hostId+recv.arenaId+'.txt');
		}
		var isImmediatelyOwned = synchronizer['Arena'][recv.arenaId].Length() === 0;

		// 現状ROOM_TYPE_ARENAにしてるけど、受信パラメータで設定することになると思う
		socket.player.roomType = label.ROOM_TYPE_ARENA;
		socket.player.side = 0;
		// とりあえず、何も考えずにarenaにjoin
		// recv.battle_field_idになると思う
		room.join(socket, recv.arenaId, function (res, roomId, roomInfo, createRoom) {
			if ( res == label.SYNC_RES_NG ) {
				console.log('err at '+__FILE__+':'+__LINE__);
				socket.disconnect();
				return;
			}
			// 自分のIDが無い
			var myId = -1;
			var isMaster = false;
			if ( res == label.SYNC_RES_OK ) {
				myId = room.getLid(roomInfo, socket.id);
				
				// 自分のIDが無い
				if ( myId == -1 ) {
					console.log('err at '+__FILE__+':'+__LINE__);
					socket.disconnect();
					return;
				}
				
				socket.player.arenaId  = roomId;
				socket.player.masterId = roomInfo.masterId;
				socket.player.masterNo = roomInfo.masterNo;
				isMaster = (socket.player.masterId == socket.id);
				
				console.log(createRoom ? 'create room:[' + roomId + ']' : 'join room:[' + roomId +']');

				if (createRoom == true) {
					roomIds.push(roomId);
					console.log("roomIds push:"+roomId);
					for(var i of roomIds) {
						console.log("roomIds roomId: " + i);
					}
				}
			}
			// 入室に成功したことを通知
			socket.json.emit(label.SYNC_MSG_ENTRY_ARENA, {id: socket.id, resultCode: res, arenaId: recv.arenaId, isMaster: isMaster});
			if ( res == label.SYNC_RES_OK )
			{
				// arenaにいるほかのキャラクター情報を送信
				root.sendSavedComponentsTo(socket, recv.townId, recv.arenaId, isImmediatelyOwned);
				// 全員へ送信
				root.to(recv.arenaId).json.emit(label.SYNC_MSG_ROOM_INFO, {id: socket.id, resultCode: label.SYNC_RES_OK, room: roomInfo});
			}
			// タウンでマスターになれないことにする
			room.setIgnoreMaster(socket, socket.player.townId, true, function (res, roomInfo, changeMaster) {
				if ( res == label.SYNC_RES_NG ) {
					console.log('err at '+__FILE__+':'+__LINE__);
					socket.disconnect();
					return;
				}

				// タウンメンバーへ通知
				root.to(socket.player.townId).json.emit(label.SYNC_MSG_ROOM_INFO, {id: socket.id, resultCode: label.SYNC_RES_OK, room: roomInfo});
				if (changeMaster) {
					if (roomInfo.masterNo > -1) {
						root.to(roomInfo.masterId).json.emit(label.SYNC_MSG_CHANGE_MASTER, {id: socket.id, isMaster: true, resultCode: label.SYNC_RES_OK});
					}
				}
			});
			socket.player.lastAccess = currentDate;
		});
	});

	// Arena退室
	// SYNC_MSG_LEAVE_BATTLE_FIELDになると思う
	socket.on(label.SYNC_MSG_LEAVE_ARENA, function (recv) {
		// とりあえず、退室
		// Unity側でtownへシーン切り替えする際にSynchronizerObject.OnDestroyで
		// WebsocketService.Instance.UnregisterGameObjectが呼ばれて、
		// deletedGameObjects_にaddされていい感じに処理してくれたらうれしいけど、
		// 削除できるようにして言うたほうがいいかも
		// for (var deleteObjectId in recv.objectIds) {
		// 	synchronizer['Arena'][recv.arenaId].deleteGameObject(deleteObjectId);
		// 	if (synchronizer['Arena'][recv.arenaId].Length() == 0) {
		// 		delete synchronizer['Arena'][recv.arenaId];
		// 	}
		// }
		// recv.battle_field_idになると思う
		room.leave(socket, recv.arenaId, function(res, roomInfo, memInfo, delRoom, changeMaster) {
			if ( res != label.SYNC_RES_OK ) {
				console.log('err at '+__FILE__+':'+__LINE__);
				socket.disconnect();
				return;
			}
			
			//
			console.log('leave room: ' + socket.player.arenaId);
			if (delRoom == true) {
				console.log('delete room: ' + socket.player.arenaId);
				if (roomIds.indexOf(socket.player.arenaId) >= 0) {
					roomIds.some(function(v,i){
						if (v == socket.player.arenaId) {
							roomIds.splice(i,1);
						}
					});
				}
			}

			memInfo.leave = 1;

			socket.player.arenaId = '';
			socket.player.masterId = null;

			// 退室に成功したことを通知
			socket.json.emit(label.SYNC_MSG_LEAVE_ARENA, {id: socket.id, resultCode: label.SYNC_RES_OK});
			if (delRoom == false) {
				root.to(recv.arenaId).json.emit(label.SYNC_MSG_MEMBER_EXIT, {id: socket.id, resultCode: label.SYNC_RES_OK, roomId: recv.arenaId, member: memInfo});
				root.to(recv.arenaId).json.emit(label.SYNC_MSG_ROOM_INFO, {id: socket.id, resultCode: label.SYNC_RES_OK, room: roomInfo});
				// マスターが変更された場合は新しいマスターへ通知
				if (changeMaster) {
					root.to(roomInfo.masterId).json.emit(label.SYNC_MSG_CHANGE_MASTER, {id: socket.id, isMaster: true, resultCode: label.SYNC_RES_OK});
				}
			}
			socket.player.roomType = label.ROOM_TYPE_TOWN;
			room.setIgnoreMaster(socket, socket.player.townId, false, function (res, roomInfo, changeMaster) {
				if ( res == label.SYNC_RES_NG ) {
					console.log('err at '+__FILE__+':'+__LINE__);
					socket.disconnect();
					return;
				}

				socket.player.masterId = roomInfo.masterId;
				socket.player.masterNo = roomInfo.masterNo;
				// タウンでマスターになってもいいよとしたときに、マスターになったらマスターになったことを通知、マスターになってなくてもマスターでないことをしならないといけない
				var isMaster = false;
				if (roomInfo.masterId == socket.id)
				{
					isMaster = true;
				}
				socket.json.emit(label.SYNC_MSG_CHANGE_MASTER, {id: socket.id, isMaster: isMaster, resultCode: label.SYNC_RES_OK});
				// タウンに戻ったら、room情報の再取得とroomメンバーへ情報を更新させる
				root.to(socket.player.townId).json.emit(label.SYNC_MSG_ROOM_INFO, {id: socket.id, resultCode: label.SYNC_RES_OK, room: roomInfo});
				// 自分がマスターではない場合も通知する
				if (changeMaster && roomInfo.masterId != socket.id) {
					root.to(roomInfo.masterId).json.emit(label.SYNC_MSG_CHANGE_MASTER, {id: socket.id, isMaster: true, resultCode: label.SYNC_RES_OK});
				}
			});
			socket.player.lastAccess = currentDate;
		});
	});

	socket.on(label.SYNC_MSG_IGNORE_MASTER, function(recv) {
		room.setIgnoreMaster(socket, recv.roomId, recv.ignore, function (res, roomInfo, changeMaster) {
			if ( res == label.SYNC_RES_NG ) {
				console.log('err at '+__FILE__+':'+__LINE__);
				socket.disconnect();
				return;
			}

			if (changeMaster) {
				var isMaster = false;
				if (roomInfo.masterId == socket.id)
				{
					isMaster = true;
				}
				socket.json.emit(label.SYNC_MSG_CHANGE_MASTER, {id: socket.id, isMaster: isMaster, resultCode: label.SYNC_RES_OK});
				// 自分がマスターではない場合も通知する
				if (roomInfo.masterNo > -1 && roomInfo.masterId != socket.id) {
					root.to(roomInfo.masterId).json.emit(label.SYNC_MSG_CHANGE_MASTER, {id: socket.id, isMaster: true, resultCode: label.SYNC_RES_OK});
				}
			}
		});
	});

	socket.on(label.SYNC_MSG_UNREGISTER_GAMEOBJECT, function (recv) {
		var roomType = '';
		var roomId = '';
		if (recv.arenaId != '') {
			roomType = 'Arena';
			roomId = recv.arenaId;
		} else if (recv.townId != '') {
			roomType = 'Town';
			roomId = recv.townId;
		} else {
			return;
		}
		for (var deleteObjectId in recv.objectIds) {
			synchronizer[roomType][roomId].deleteObject(deleteObjectId);
		}
		if (roomType == 'Arena') {
			if (synchronizer[roomType][roomId].Length() == 0) {
				delete synchronizer[roomType][roomId];
			}
		}
	});

	socket.on(label.SYNC_MSG_SYNCHRONIZEDATA, function (recv) {
		// console.log('socket.on host['+hostId+']['+label.SYNC_MSG_SYNCHRONIZEDATA+'] time['+(new Date().getTime())+']');
		var buf = new Buffer(recv.data, 'base64');
		var unpackdata = msgpack.unpack(buf);
		// console.log(label.SYNC_MSG_SYNCHRONIZEDATA + ": " + unpackdata.data);
		if (recv.arenaId != '') {
			synchronizer['Arena'][recv.arenaId].add(unpackdata.data);
		} else if (recv.townId != '') {
			synchronizer['Town'][recv.townId].add(unpackdata.data);
		}
		unpackdata.townId  = recv.townId;
		unpackdata.arenaId = recv.arenaId;
		unpackdata.hostId  = hostId;
		redisEvPub.publish(label.SYNC_MSG_SYNCHRONIZEDATA, JSON.stringify(unpackdata));
		socket.player.lastAccess = currentDate;
	});

	socket.on(label.SYNC_MSG_CHAT, function(recv) {
		console.log("on message recieve:" + recv.message);
		var roomId = '';
		var roomType = label.ROOM_TYPE_TOWN;
		if (recv.arenaId != '' && (recv.arenaId in synchronizer['Arena'])) {
			roomId = recv.arenaId;
			roomType = label.ROOM_TYPE_ARENA;
		} else if (recv.townId != '' && (recv.townId in synchronizer['Town'])) {
			roomId = recv.townId;
			roomType = label.ROOM_TYPE_TOWN;
		} else {
			return;
		}
		recv.hostId = hostId;
		root.to(roomId).json.emit(label.SYNC_MSG_CHAT, {resultCode: label.SYNC_RES_OK, roomType: roomType, message: recv.message});
		//redisEvPub.publish(label.SYNC_MSG_CHAT, JSON.stringify(recv));
		socket.player.lastAccess = currentDate;
	});

	// 切断
	socket.on('disconnect', function (reason) {
		console.log('socket.on host['+hostId+'][disconnect] time['+formatDate(new Date(), 'YYYY/MM/DD hh:mm:ss')+']');
		if ( socket.player.timerId ) {
			clearInterval(socket.player.timerId);
			socket.player.timerId = false;
		}
		redisKVS.decr(label.MULTIPLAY_SELECT_SERVER_COUNTER);

		// disconnectされたgameObjectはisTakenOverToMaster == trueでない場合に
		// 一定時間更新しなければ削除(DestroyImmediate)される

		if (socket.player.arenaId != '') {
			room.leave(socket, socket.player.arenaId, function(res, roomInfo, memInfo, delRoom, changeMaster) {
				if ( res == label.SYNC_RES_OK ) {
					console.log('leave room: ' + socket.player.arenaId);
					if ( delRoom )
						console.log('delete room: ' + socket.player.arenaId);

					memInfo.leave = 2;
					if (delRoom == false) {
						root.to(socket.player.arenaId).json.emit(label.SYNC_MSG_MEMBER_EXIT, {id: socket.id, resultCode: label.SYNC_RES_OK, roomId: socket.player.arenaId, member: memInfo});
						root.to(socket.player.arenaId).json.emit(label.SYNC_MSG_ROOM_INFO, {id: socket.id, resultCode: label.SYNC_RES_OK, room: roomInfo});
						// マスターが変更された場合は新しいマスターへ通知
						if (changeMaster) {
							root.to(roomInfo.masterId).json.emit(label.SYNC_MSG_CHANGE_MASTER, {id: socket.id, isMaster: true, resultCode: label.SYNC_RES_OK});
							console.log("change master to "+roomInfo.masterId+" master playerId["+roomInfo.playerId+"]");
						}
					}
					else
					{
						// roomが削除される場合は同期データも不要になるので削除
						synchronizer['Arena'][socket.player.arenaId].release();
						delete synchronizer['Arena'][socket.player.arenaId];
						if (roomIds.indexOf(socket.player.arenaId) >= 0) {
							roomIds.some(function(v,i){
								if (v == socket.player.arenaId) {
									roomIds.splice(i,1);
								}
							});
						}
					}
				}

			});
		}
		if (socket.player.townId != '') {
			room.leave(socket, socket.player.townId, function(res, roomInfo, memInfo, delRoom, changeMaster) {
				if ( res == label.SYNC_RES_OK ) {
					console.log('leave room: ' + socket.player.townId);
					if ( delRoom )
						console.log('delete room: ' + socket.player.townId);

					memInfo.leave = 2;
					if (delRoom == false) {
						console.log("change master:"+changeMaster);
						root.to(socket.player.townId).json.emit(label.SYNC_MSG_MEMBER_EXIT, {id: socket.id, resultCode: label.SYNC_RES_OK, roomId: socket.player.townId, member: memInfo});
						root.to(socket.player.townId).json.emit(label.SYNC_MSG_ROOM_INFO, {id: socket.id, resultCode: label.SYNC_RES_OK, room: roomInfo});
						// マスターが変更された場合は新しいマスターへ通知
						if (changeMaster) {
							root.to(roomInfo.masterId).json.emit(label.SYNC_MSG_CHANGE_MASTER, {id: socket.id, isMaster: true, resultCode: label.SYNC_RES_OK});
							console.log("change master to "+roomInfo.masterId+" master playerId["+roomInfo.playerId+"]");
						}
					}
					else
					{
						// roomが削除される場合は同期データも不要になるので削除
						synchronizer['Town'][socket.player.townId].release();
						delete synchronizer['Town'][socket.player.townId];
						if (roomIds.indexOf(socket.player.townId) >= 0) {
							roomIds.some(function(v,i){
								if (v == socket.player.townId) {
									roomIds.splice(i,1);
								}
							});
						}
					}
				}

			});
		}

		amqp.connect(setting.amqp.url, function (err, conn) {
			if ( err ) {
				console.log("amqp connection error");
				return;
			}
			conn.createChannel(function (err, ch) {
				if ( err ) {
					console.log("amqp createChannel error");
					return;
				}
				var chname = 'disconnected';
				ch.assertQueue(chname, {durable: false});

				var obj = new Object();
				obj.townId   = socket.player.townId;
				obj.arenaId  = socket.player.arenaId;
				obj.playerId = socket.player.playerId;
				ch.sendToQueue(chname, new Buffer(JSON.stringify(obj)), {persistent: false}, function (err, ok) {
					if ( err ) {
						console.warn("Message nacked!");
					} else {
						console.log("Message acked");
					}
				});
			});
			setTimeout(function() {
				conn.close();
			}, 500);
		});
		if ( --processInfo.totalCount < 0 ) processInfo.totalCount = 0;
		// console.log('disconnect reason:['+reason+']', socket);
	});

	// interval timer ping以外の通信が一定時間行われない場合はタイムアウトにする仕組み
	socket.player.timerId = setInterval(function() {
		if ( !socket.player.blank && (currentDate - socket.player.lastAccess) >= label.BLANK_TIMEOUT ) {
			socket.player.blank = true;
			
			console.log('blank timeout, disconnect. playerId:'+socket.player.playerId);
			
			try {
				// 自動切断するからクライアントで先にSocketIOComponent切断してね				
				socket.json.emit(label.SYNC_MSG_SYSTEM, {id: socket.id, cmd: label.SYNC_SYSCALL_INTERVAL_EXIT, resultCode: label.SYNC_RES_OK});
				// 切断されていなかったら強制切断
				setTimeout(function() {
					if ( !socket.disconnected )
						socket.disconnect();
				}, 5000);
			} catch (ex) {
				socket.disconnect();
			}
		}
	}, 5000);

	// engine.io内SocketオブジェクトのsetPingTimeoutにメンバーを追加（個別のpingTimeout値）
	if ( !socket.conn.pingInterval )
		socket.conn.pingInterval = socket.conn.server.pingInterval;
	if ( !socket.conn.pingTimeout )
		socket.conn.pingTimeout = socket.conn.server.pingTimeout;
	
	// engine.io内SocketオブジェクトのsetPingTimeoutをオーバーライド
	socket.conn.setPingTimeout = function () {
		var self = this;
		clearTimeout(self.pingTimeoutTimer);
		self.pingTimeoutTimer = setTimeout(function () {
			self.onClose('ping timeout custom 2');
		}, self.pingInterval + self.pingTimeout);
	};
});

console.log("start server ======================== "+port);
var server = io.listen(
	setting.ssl ?
	require('https').createServer({
		key: fs.readFileSync('/etc/nginx/cert.key'),
		cert: fs.readFileSync('/etc/nginx/cert.crt')
	}).listen(port)
	:
	port,
	{
		'pingInterval' : label.SOCKET_PING_INTERVAL,
		'pingTimeout'  : label.SOCKET_PING_TIMEOUT,
	}
);
// 
server.set('transports', ['websocket']);

var getInfo = function(index, clientNum) {
	var command   = 'i';
	var isMaster  = index === 0 ? 'true' : 'false';
	var timestamp = +new Date();
	return [command, isMaster, timestamp, clientNum].join('\t');
};

root.broadcast = function(townId, arenaId, data) {
	var roomId = '';
	if (arenaId != '') {
		roomId = arenaId;
	} else if (townId != '') {
		roomId = townId;
	} else {
		return;
	}
	if (typeof(io.sockets.adapter.rooms[roomId]) == "undefined") {
		return;
	}
	if (typeof(io.sockets.adapter.rooms[roomId].sockets) == "undefined") {
		return;
	}
	var keys = Object.keys(io.sockets.adapter.rooms[roomId].sockets);
	var length = keys.length;
	// console.log("root.broadcast client length:"+length);
	if (length !== 0) {
		for (var index = 0; index < length; index++) {
			var info = getInfo(index, length);
			var message = info + '\n' + data;
			// console.log("broadcast client.id["+root.sockets[keys[index]].id+"] message["+message+"]");
			var obj          = new Object();
			obj.id           = keys[index];
			obj.data         = message;
			var base64string = new Buffer(JSON.stringify(obj)).toString('base64');
			root.sockets[keys[index]].json.emit(label.SYNC_MSG_SYNCHRONIZEDATA, {resultCode: label.SYNC_RES_OK, hostId: hostId, townId: townId, arenaId: arenaId, data: base64string});
		}
	}
};

root.sendSavedComponentsTo = function(socket, townId, arenaId, isImmediatelyOwned) {
	var sync = false;
	var messages = '';
	if (arenaId != '') {
		sync = synchronizer['Arena'][arenaId];
	} else if (townId != '') {
		sync = synchronizer['Town'][townId];
	} else {
		return;
	}
	if (sync == null) {
		return;
	}
	var messages = sync.getSavedComponentsMessages(isImmediatelyOwned);
	// console.log("sendSavedComponentsTo: "+messages);

	var obj          = new Object();
	obj.id           = socket.id;
	obj.data         = messages;
	var base64string = new Buffer(JSON.stringify(obj)).toString('base64');
	socket.json.emit(label.SYNC_MSG_SYNCHRONIZEDATA, {resultCode: label.SYNC_RES_OK, hostId: hostId, townId: townId, arenaId: arenaId, data: base64string});
};

// パケット送信
function sendPacket(socket, data, msg, func) {
	// console.log('sendPacket: emit host['+hostId+'] msg['+msg+'] time['+(new Date().getTime())+']', socket);
	socket.json.emit(msg, { id: socket.id, data: JSON.stringify(data) });
}

function cpuAverage()
{
	//Initialise sum of idle and time of cores and fetch CPU info
	var totalIdle = 0, totalTick = 0;
	var cpus = os.cpus();
	//Select CPU core
	var cpu = cpus[workerId];
	//Total up the time in the cores tick
	for (var type in cpu.times) {
		totalTick += cpu.times[type];
	}	 
	//Total up the idle time of the core
	totalIdle += cpu.times.idle;
	//Return the average Idle and Tick times
	return { idle: totalIdle / cpus.length, total: totalTick / cpus.length };
}
// Global Timer.
var pi_count = setting.pi_time;
// var gc_count = setting.gc_time;
var startMeasure = cpuAverage();
var globalTimer = setInterval(function() {
	var oDate = (new Date());
	currentDate = oDate.getTime();
	
	// メンテナンススケジュール取得
	// getMaintenaceSchedule();
	
	// aMainteType.forEach( function(mainteType) {
	// 	// メンテチェック
	// 	var maintIn = isMaintenance( mainteType );
		
	// 	var tempkey = ":"+mainteType;
	// 	if ( maintIn && !aBeforeMaintMode[tempkey] ) {
	// 		maintenanceIn(mainteType);
	// 	}
	// 	if ( !maintIn && aBeforeMaintMode[tempkey] ) {
	// 		console.sinfo("maintenance type:"+mainteType+" out.");
	// 	}
	// 	aBeforeMaintMode[tempkey] = maintIn;
	// });
	
	// プロセス情報
	pi_count -= 1000;
	if (pi_count <= 0) {
		var endMeasure = cpuAverage(); 
		var idleDifference = endMeasure.idle - startMeasure.idle;
		var totalDifference = endMeasure.total - startMeasure.total;
		var percentageCPU = 100 - ~~(100 * idleDifference / totalDifference);
		startMeasure = endMeasure;
		
		var key = label.REDIS_PROCESS_INFO_PREFIX + hostId;
		var expire = parseInt(setting.pi_time/1000,10)+10;
		processInfo.sockets = Object.keys(root.sockets).length;				// socket接続数
		processInfo.rooms = Object.keys(root.adapter.rooms).length;
		processInfo.memory = process.memoryUsage();							// 使用メモリー
		processInfo.cpu = percentageCPU;									// CPU使用%
		
		var aTempSockNum = [];
		aTempSockNum[label.ROOM_TYPE_TOWN] = 0;
		aTempSockNum[label.ROOM_TYPE_ARENA] = 0;
		aTempSockNum[label.ROOM_TYPE_PVP] = 0;
		for ( var sockId in root.sockets ) {
			if ( !root.sockets[sockId].player.admin ) {
				var roomType = root.sockets[sockId].player.roomType;
				if ( roomType in aTempSockNum )
					aTempSockNum[roomType]++;
			}
		}
		processInfo.aSockNum = aTempSockNum;
		
		var aTempRoomNum = [];
		aTempRoomNum[label.ROOM_TYPE_TOWN] = 0;
		aTempRoomNum[label.ROOM_TYPE_ARENA] = 0;
		aTempRoomNum[label.ROOM_TYPE_PVP] = 0;
		for ( var roomId in root.adapter.rooms ) {
			if ( roomId.charAt(0) != '/' ) {
				for ( var sockId in root.adapter.rooms[roomId].sockets ) {
					if ( sockId in root.sockets ) {
						var roomType = root.sockets[sockId].player.roomType;
						if ( roomType in aTempRoomNum )
							aTempRoomNum[roomType]++;
						break;
					}
				}
			}
		}
		processInfo.aRoomNum = aTempRoomNum;
		
		redisKVS.setex(key, expire, JSON.stringify(processInfo));
		// console.log("processInfo set key[" + key + "]");
		pi_count += setting.pi_time;
		
		// 監視
		// rebootManager.watch(processInfo, currentDate);
	}
	
	// 継続時間
	processInfo.continuation++;
	
}, 1000);


var synchronizeTimer = function() {
	for (var townId in synchronizer['Town']) {
		// if (roomIds.indexOf(townId) >= 0) {
			console.log("hostId["+hostId+"] townId["+townId+"] is exists. broadcast");
			var sync = synchronizer['Town'][townId];
			if (sync.hasMessages()) {
				var messages = sync.getMessages();
				root.broadcast(townId, '', messages);
			}
		// } else {
		// 	console.log("hostId["+hostId+"] townId["+townId+"] is not exists");
		// }
	}
	for (var arenaId in synchronizer['Arena']) {
		// if (roomIds.indexOf(arenaId) >= 0) {
			console.log("hostId["+hostId+"] arenaId["+arenaId+"] is exists. broadcast");			
			var sync = synchronizer['Arena'][arenaId];
			if (sync.hasMessages()) {
				var messages = sync.getMessages();
				root.broadcast('', arenaId, messages);
			}
		// } else {
		// 	console.log("hostId["+hostId+"] arenaId["+arenaId+"] is not exists");			
		// }
	}
	setTimeout(synchronizeTimer, 1000 / setting.fps);
};

synchronizeTimer();

/**
 * 日付をフォーマットする
 * @param  {Date}   date     日付
 * @param  {String} [format] フォーマット
 * @return {String}          フォーマット済み日付
 */
var formatDate = function (date, format) {
  if (!format) format = 'YYYY-MM-DD hh:mm:ss.SSS';
  format = format.replace(/YYYY/g, date.getFullYear());
  format = format.replace(/MM/g, ('0' + (date.getMonth() + 1)).slice(-2));
  format = format.replace(/DD/g, ('0' + date.getDate()).slice(-2));
  format = format.replace(/hh/g, ('0' + date.getHours()).slice(-2));
  format = format.replace(/mm/g, ('0' + date.getMinutes()).slice(-2));
  format = format.replace(/ss/g, ('0' + date.getSeconds()).slice(-2));
  if (format.match(/S/g)) {
    var milliSeconds = ('00' + date.getMilliseconds()).slice(-3);
    var length = format.match(/S/g).length;
    for (var i = 0; i < length; i++) format = format.replace(/S/, milliSeconds.substring(i, i + 1));
  }
  return format;
};