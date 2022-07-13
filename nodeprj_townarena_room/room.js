/**
 * Room
 */
var label = require('./labels.js');

//
module.exports = function(redisMaster, redisSlave, roomConf, debug) {
	return new Room(redisMaster, redisSlave, roomConf, debug);
}

//
function Room(redisMaster, redisSlave, roomConf, debug)
{
	// redis pool
	this.poolMaster   = redisMaster;
	this.poolSlave    = redisSlave;
	this.expire       = roomConf.expire || 3600;
	this.hostId       = roomConf.hostId || '???';
	this.debug        = debug;
}

// 部屋情報(Redis)
Room.prototype.createRoomObject = function()
{
	return {
		roomId       : 'none',
		roomType     : 0,
		masterId     : 0,
		masterNo     : 0,
		playerId     : 'none',
		players      : [],
		side_players : [],
		roomState    : label.ROOM_STATE_IDLE,
		maxPlayers   : 0,
		created      : 0,
	};
}

// 部屋のメンバー情報
Room.prototype.createMemberObject = function(_id, _name)
{
	return {
		id          : _id,
		lid         : -1,
		playerId    : 'none',
		name        : _name,
		bIgnoreMaster : false,
		side        : -1,
		accessSpeed : -1,
		leave       : 0,
	};
}

// 非同期コールバックを呼ばれた場合のエラー処理にthrowが使えない為、client関連をutility化
Room.prototype.getWatcher = function(roomName, socket, callback) {
	var expire = this.expire;
	
	this.poolMaster.getWatcher(roomName, expire, socket, function(res, val, work) {
		var roomObj = val ? JSON.parse(val) : null;
		callback(res, roomObj, work);
	});
}

//メンバー入室
Room.prototype.join = function(socket, roomName, callback, retry)
{
	if ( typeof(retry) == "undefined" ) { retry = 0; }

	var name         = socket.player.name;
	var that         = this;
	var timeoutFunc  = function(retry) { that.join(socket, roomName, callback, retry); }
	console.log("room.join roomName["+roomName+"] playerId["+socket.player.playerId+"]");

	function callFunc(socket, roomName, mode, roomObj, name, callback) {
		if ( roomObj == null ) {
			that._create(socket, roomName, mode, name, callback);
		} else {
			that._join(socket, roomName, mode, roomObj, name, callback);
		}
	}

	that.getWatcher(roomName, socket, function(res, roomObj, work) {
		if ( res != label.SYNC_RES_OK ) {
			// console.log('err('+res+') at '+__FILE__+':'+__LINE__, socket);
			callback(res);
			return;
		}

		var cflag = false;
		var mode = label.RT_JOIN_MODE_JOIN;
		if ( roomObj == null )
		{
			cflag = true;
			mode = label.RT_JOIN_MODE_CREATE;
		}

		callFunc(socket, roomName, mode, roomObj, name, function(res, roomObj) {
			// console.log("room.join callFunc callbacked!!");
			if ( res != label.SYNC_RES_OK ) {
//					console.serror('err('+res+') at '+__FILE__+':'+__LINE__, socket);
				work.release();
				callback(res);
				return;
			}
			work.setex(JSON.stringify(roomObj), retry, timeoutFunc, function(err) {
				if ( err ) {
					// console.serror('err('+err+') at '+__FILE__+':'+__LINE__, socket);
					work.release();
					callback(label.SYNC_RES_NG);
					return;
				}
				
				socket.join(roomName);
				work.release();
				// console.log("room.join redisWatcher setex callbacked socket.joined!");
				callback(label.SYNC_RES_OK, roomName, roomObj, cflag);
			});
		});

	});
}

//部屋作成実処理
Room.prototype._create = function(socket, roomName, mode, name, callback) {
	var roomType     = socket.player.roomType;
	var playerId     = socket.player.playerId;
	var maxPlayers   = socket.player.maxPlayers;
	var side         = socket.player.side;
	var hostId       = this.hostId;
	var that         = this;
	var created      = new Date().getTime();
	var accessSpeed  = created - socket.player.timeStamp;

	if ( mode & label.RT_JOIN_MODE_CREATE ) {
		var roomObj = that.createRoomObject();
		roomObj.roomId       = roomName;
		roomObj.roomType     = roomType;
		roomObj.masterId     = socket.id; // master id
		roomObj.masterNo     = 0; // master id
		roomObj.maxPlayers   = maxPlayers;
		roomObj.playerId     = playerId;
		roomObj.created      = created;
		roomObj.side_players[0] = [];
		roomObj.side_players[1] = [];

		roomObj.roomState = label.ROOM_STATE_READY;
		
		var member  = that.createMemberObject(socket.id, name);
		member.lid         = 0; // master id
		member.playerId    = playerId;
		member.accessSpeed = accessSpeed;
		member.bIgnoreMaster = false;

		roomObj.players.push(member);
		if (roomType == label.ROOM_TYPE_PVP) {
			roomObj.side_players[side].push(member);
		}
		callback(label.SYNC_RES_OK, roomObj);

	} else {
		callback(label.SYNC_RES_ROOM_NOT_FOUND);
	}
}

//部屋入室実処理
Room.prototype._join = function(socket, roomName, mode, roomObj, name, callback) {
	var roomType     = socket.player.roomType;
	var playerId     = socket.player.playerId;
	var side         = socket.player.side;
	var hostId       = this.hostId;
	var that         = this;
	var accessSpeed  = ( new Date().getTime() ) - socket.player.timeStamp;

	if ( mode & label.RT_JOIN_MODE_JOIN ) {
		if (roomType == label.ROOM_TYPE_ARENA) {
			if (roomObj.players.length == label.ARENA_ENTRY_MAX) {
				callback(label.SYNC_RES_ROOM_FULL);
				return;
			}
		}
		else if (roomType == label.ROOM_TYPE_PVP) {
			if (roomObj.side_players[side].length == label.PVP_SIDE_MAX) {
				// console.log("over capacity side player room:"+side_players[side].length+" user:["+socket.id+"]", socket);
				callback(label.SYNC_RES_ROOM_FULL);
				return;
			}
		}
		var member  = that.createMemberObject(socket.id, name);
		member.playerId    = playerId;
		member.accessSpeed = accessSpeed;
		member.bIgnoreMaster = false;

		// 既にidは登録されている
		if ( roomObj.players.some(function(val) { return val.id == socket.id; }) ) {
			// console.log("already joined user:["+socket.id+"]");
			callback(label.SYNC_RES_ROOM_ALREADY);
			return;
		}
		// room内idの決定
		for (var i=0; i<roomObj.maxPlayers; i++) {
			if ( roomObj.players.some(function(val) { return val.lid == i; }) )
				continue;
			member.lid = i;
			break;
		}
		// idが決まらない
		if ( member.lid == -1 ) {
			// console.log("over capacity room:"+roomObj.players.length+" user:["+socket.id+"]");
			callback(label.SYNC_RES_ROOM_FULL);
			return;
		}

		if (roomObj.masterNo == -1) {
			// マスターがいない状態の場合
			roomObj.masterId = socket.id;
			roomObj.masterNo = member.lid;
			roomObj.playerId = playerId;
		}

		roomObj.players.push(member);
		if (roomType == label.ROOM_TYPE_PVP) {
			roomObj.side_players[side].push(member);
		}

		callback(label.SYNC_RES_OK, roomObj);

	} else {
		callback(label.SYNC_RES_NG);
	}
}

Room.prototype.setIgnoreMaster = function(socket, roomName, bIgnore, callback, retry)
{
	if ( typeof(retry) == "undefined" ) { retry = 0; }

	var that         = this;
	var timeoutFunc = function(retry) { that.ignoreMaster(socket, roomName, bIgnore, callback, retry); }
	console.log("room.setIgnoreMaster roomName["+roomName+"] playerId["+socket.player.playerId+"] Ignore["+bIgnore+"]");

	this.getWatcher(roomName, socket, function(res, roomObj, work) {
		if ( res != label.SYNC_RES_OK ) {
			// console.log('err('+res+') at '+__FILE__+':'+__LINE__, socket);
			callback(res);
			return;
		}

		if ( roomObj == null ) {
			if ( that.debug || retry == 0 )
				// console.log('ignoreMaster:room info is null at '+__FILE__+':'+__LINE__, socket);
			work.release();
			callback(label.SYNC_RES_NG);
			return;
		}

		var bChangeMaster = false;
		var mymember;
		// roomのmemberのsocket.idを探して、socket.idが一致したmemberのbIgnoreMasterを書き換える
		for ( var i = 0; i < roomObj.players.length; i++ ) {
			if ( roomObj.players[i].id == socket.id ) {
				roomObj.players[i].bIgnoreMaster = bIgnore;
				mymember = roomObj.players[i];
				break;
			}
		}
		if (bIgnore && roomObj.masterId == socket.id) {
			// マスターになれなくなった際にマスターだったら、マスターになれるメンバーを探してマスターにする
			var prevSpeed    = -1;
			var masterId     = '';
			var masterNo     = -1;
			var masterPlayerId = '';
			for ( var i = 0; i < roomObj.players.length; i++ ) {
				if ( prevSpeed > roomObj.players[i].accessSpeed || prevSpeed == -1 && roomObj.players[i].bIgnoreMaster == false ) {
					prevSpeed       = roomObj.players[i].accessSpeed;
					masterId        = roomObj.players[i].id;			// master socket.id
					masterNo        = roomObj.players[i].lid;			// master member count(id)
					masterPlayerId  = roomObj.players[i].playerId;
				}
			}
			// 入室時間の一番古いメンバーを新たなマスターに設定
			roomObj.masterId  = masterId;
			roomObj.masterNo  = masterNo;
			roomObj.playerId  = masterPlayerId;
			bChangeMaster = true;
		}
		else
		{
			// マスターになれるようになった際にマスターがいない場合
			if (roomObj.masterNo == -1) {
				roomObj.masterId = mymember.id;			// master socket.id
				roomObj.masterNo = mymember.lid;			// master member count(id)
				roomObj.playerId = mymember.playerId;
				bChangeMaster = true;
			}
		}

		work.setex(JSON.stringify(roomObj), retry, timeoutFunc, function(err) {
			if ( err ) {
				// console.log('err('+err+') at '+__FILE__+':'+__LINE__, socket);
				work.release();
				callback(label.SYNC_RES_NG);
				return;
			}
			
			work.release();
			callback(label.SYNC_RES_OK, roomObj, bChangeMaster);
		});
	});
}

//メンバー退出
Room.prototype.leave = function(socket, roomName, callback, retry)
{
	if ( typeof(retry) == "undefined" ) { retry = 0; }

	var roomType     = socket.player.roomType;
	var side         = socket.player.side;
	var that         = this;
	var timeoutFunc  = function(retry) { that.leave(socket, roomName, callback, retry); };
	console.log("room.leave roomName["+roomName+"] playerId["+socket.player.playerId+"]");

	this.getWatcher(roomName, socket, function(res, roomObj, work) {
		if ( res != label.SYNC_RES_OK ) {
			// console.log('err('+res+') at '+__FILE__+':'+__LINE__, socket);
			callback(res);
			return;
		}

		if ( roomObj == null ) {
			// if ( that.debug || retry == 0 )
			// 	console.log('leave:room info is null at '+__FILE__+':'+__LINE__, socket);
			work.release();
			callback(label.SYNC_RES_NG);
			return;
		}

		// メンバー削除
		var mydata = null;
		// 退室ユーザー以外のメンバーにroomObjを書き換える
		roomObj.players = roomObj.players.filter(function (mem, i) {
			if ( mem.id == socket.id )
				mydata = mem;
			return mem.id != socket.id;
		});

		// アリーナの場合は勢力メンバーリストからも削除
		if (roomType == label.ROOM_TYPE_PVP) {
			roomObj.side_players[side] = roomObj.side_players[side].filter(function (mem, i) {
				return mem.id != socket.id;
			});
		}
		// 見つからなかった場合
		if ( mydata == null ) {
			work.release();
			// if ( that.debug )
			// 	console.log('member info not found, uid:'+socket.player.playerId+'] sid:['+socket.id+']', socket);
			// console.log('member info:'+JSON.stringify(roomObj.players)+'');
			return;
		}

		var bChangeMaster = false;
		// 退室メンバーがマスターだった場合
		if ( roomObj.masterNo == mydata.lid && roomObj.masterId == socket.id ) {
			// 入室時間が一番古いメンバーを探す
			var prevSpeed    = -1;
			var masterId     = '';
			var masterNo     = -1;
			var masterPlayerId = '';
			for ( var i = 0; i < roomObj.players.length; i++ ) {
				if ( prevSpeed > roomObj.players[i].accessSpeed || prevSpeed == -1 && roomObj.players[i].bIgnoreMaster == false ) {
					prevSpeed       = roomObj.players[i].accessSpeed;
					masterId        = roomObj.players[i].id;			// master socket.id
					masterNo        = roomObj.players[i].lid;			// master member count(id)
					masterPlayerId  = roomObj.players[i].playerId;
				}
			}
			// 入室時間の一番古いメンバーを新たなマスターに設定
			roomObj.masterId  = masterId;
			roomObj.masterNo  = masterNo;
			roomObj.playerId  = masterPlayerId;
			bChangeMaster = true;
		}

		// 人が居なければ削除
		var del = false;
		var oldState = roomObj.roomState;
		if ( roomObj.players.length == 0 ) {
			roomObj.roomState = label.ROOM_STATE_CLOSE;
			del = true;
		}

		var setVal = del ? null : JSON.stringify(roomObj);

		work.setex(setVal, retry, timeoutFunc, function(err) {
			if ( err ) {
				// console.log('err('+err+') at '+__FILE__+':'+__LINE__, socket);
				work.release();
				callback(label.SYNC_RES_NG);
				return;
			}
			
			socket.leave(roomName);
			work.release();
			callback(label.SYNC_RES_OK, roomObj, mydata, del, bChangeMaster);
		});
	});
}

// local id
Room.prototype.getLid = function(roomInfo, id) {
	for ( var i = 0; i < roomInfo.players.length; i++ ) {
		if ( roomInfo.players[i].id == id ) {
			return roomInfo.players[i].lid;
		}
	}
	return -1;
}
