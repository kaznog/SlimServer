global.__defineGetter__('__LINE__', function () { return (new Error()).stack.split('\n')[2].split(':').reverse()[1]; });
global.__defineGetter__('__FILE__', function () { return (new Error()).stack.split('\n')[2].match(/\/([^/:]*?):/)[1]; });
var cookie        = require('socket.io-client-cookies-headers');
var client        = require('socket.io-client');
var request       = require('request');
var uuidV1        = require('uuid/V1');
var crypto        = require('crypto');
var label         = require('../labels.js');
var os		      = require('os');
var hostname      = os.hostname();
var REQUEST_HASH_SECRET = 'thisisrequesthashforlocal';
var REQUEST_TYPE_BRIDGE = 0;
var REQUEST_TYPE_API    = 1;


module.exports = TestClient;
function TestClient(no, ClientOps, ServerAddress)
{
	this.socket_options = ClientOps || {
		'force new connection': true,
		'transports' : ["websocket"]
	};
	this.name                    = this.outDateString(new Date());
	this.devicePlatform          = 1;
	this.nativeToekn             = '';
	this.clientNativeSessionId   = '';
	this.sharedSecurityKey       = '';
	this.header_session_id       = '';
	this.bridgeUserId            = '';
	this.nativeSessionId         = '';
	this.gender                  = Math.floor(Math.random() * 2) + 1;
	this.playerId                = -1;
	this.LivingTown              = null;
	this.selectedTown            = null;
	this.rt_server_connect_token = '';
	this.rt_server_connect_url   = '';
	//this.devicePlatform = Math.floor(Math.random() * 2) + 1;
}

TestClient.prototype.exec = function()
{
	this.getNativeToken(this.devicePlatform);
}

TestClient.prototype.createTownObj = function()
{
	return {
		id : -1,
		inquiry_id : '',
		description : '',
		max_entries : 0,
		entries : -1
	};
}

TestClient.prototype.getNativeToken = function(devicePlatform)
{
	var that = this;
	console.log("getNativeToken");
	var endpoint = label.API_ENDPOINT_GET_NATIVE_TOKEN;
	var jsonObj = new Object();
	jsonObj.devicePlatform = devicePlatform;
	that.requestServer(REQUEST_TYPE_API, endpoint, 'POST', JSON.stringify(jsonObj), function (error, response, body) {
		var res = JSON.parse(body);
		console.log(__FILE__+":"+__LINE__+" response["+response+"] body["+JSON.stringify(body)+"] resultCode["+res.resultCode+"]");
		if (res.resultCode == label.API_RESULT_CODE_SUCCESS) {
			that.nativeToekn = res.nativeToken;
			that.getClientNativeSessionId(that.devicePlatform, that.nativeToekn);
		} else {
			console.log("getNativeToken result failure.");
		}
	});
}

TestClient.prototype.getClientNativeSessionId = function(devicePlatform, nativeToken)
{
	var that = this;
	console.log("getClientNativeSessionId");
	var jsonObj = new Object();
	jsonObj.UUID = uuidV1();
	jsonObj.deviceType = devicePlatform;
	jsonObj.nativeToken = nativeToken;
	that.requestServer(REQUEST_TYPE_BRIDGE, null, 'POST', JSON.stringify(jsonObj), function (error, response, body) {
		var res = JSON.parse(body);
		console.log(__FILE__+":"+__LINE__+" response["+response+"] body["+JSON.stringify(body)+"]");
		that.clientNativeSessionId = res.nativeSessionId;
		that.sharedSecurityKey = res.sharedSecurityKey;
		that.updateSession(that.devicePlatform, that.clientNativeSessionId);
	});
}

TestClient.prototype.updateSession = function(devicePlatform, clientNativeSessionId)
{
	var that = this;
	console.log("updateSession");
	var endpoint = label.API_ENDPOINT_UPDATE_SESS;
	var jsonObj = new Object();
	jsonObj.clientNativeSessionId = clientNativeSessionId;
	jsonObj.devicePlatform = devicePlatform;
	that.requestServer(REQUEST_TYPE_API, endpoint, 'POST', JSON.stringify(jsonObj), function (error, response, body) {
		var res = JSON.parse(body);
		console.log(__FILE__+":"+__LINE__+" response["+response+"] body["+JSON.stringify(body)+"]");
		if (res.resultCode == label.API_RESULT_CODE_SUCCESS) {
			that.bridgeUserId = res.userId;
			that.nativeSessionId = res.nativeSessionId;
			that.sharedSecurityKey = res.sharedSecurityKey;
			that.LoginBridge(that.devicePlatform, that.bridgeUserId, that.nativeSessionId, that.sharedSecurityKey);
		} else {
			console.log("updateSession result failure.");
		}
	});
}

TestClient.prototype.LoginBridge = function(devicePlatform, bridgeUserId, nativeSessionId, sharedSecurityKey)
{
	var that = this;
	console.log("LoginBridge");
	var endpoint = label.API_ENDPOINT_LOGIN_BRIDGE;
	var jsonObj = new Object();
	jsonObj.userId = bridgeUserId;
	jsonObj.nativeSessionId = nativeSessionId;
	jsonObj.sharedSecurityKey = sharedSecurityKey;
	jsonObj.devicePlatform = devicePlatform;
	that.requestServer(REQUEST_TYPE_API, endpoint, 'POST', JSON.stringify(jsonObj), function (error, response, body) {
		var res = JSON.parse(body);
		console.log(__FILE__+":"+__LINE__+" response["+response+"] body["+JSON.stringify(body)+"]");
		if (res.resultCode == label.API_RESULT_CODE_SUCCESS) {
			that.bridgeUserId = res.userId;
			that.SignUp(that.devicePlatform, that.bridgeUserId, that.name, that.gender);
		} else {
			console.log("LoginBridge result failure.");
		}
	});
}

TestClient.prototype.SignUp = function(devicePlatform, bridgeUserId, name, gender)
{
	var that = this;
	console.log("SignUp");
	var endpoint = label.API_ENDPOINT_SIGNUP;
	var jsonObj = new Object();
	jsonObj.userId = bridgeUserId;
	jsonObj.name = name;
	jsonObj.gender = gender;
	jsonObj.devicePlatform = devicePlatform;
	that.requestServer(REQUEST_TYPE_API, endpoint, 'POST', JSON.stringify(jsonObj), function (error, response, body) {
		var res = JSON.parse(body);
		console.log(__FILE__+":"+__LINE__+" response["+response+"] body["+JSON.stringify(body)+"]");
		if (res.resultCode == label.API_RESULT_CODE_SUCCESS) {
			that.playerId = res.playerId;
			that.Login(that.bridgeUserId);
		} else if (res.resultCode == label.API_RESULT_CODE_PLAYER_ALREADY_EXISTS) {
			that.Login(that.bridgeUserId);
		} else {
			console.log("SignUp result failure.");
		}
	});
}

TestClient.prototype.Login = function(bridgeUserId)
{
	var that = this;
	console.log("Login");
	var endpoint = label.API_ENDPOINT_LOGIN;
	var jsonObj = new Object();
	jsonObj.userId = bridgeUserId;
	that.requestServer(REQUEST_TYPE_API, endpoint, 'POST', JSON.stringify(jsonObj), function (error, response, body) {
		var res = JSON.parse(body);
		console.log(__FILE__+":"+__LINE__+" response["+response+"] body["+JSON.stringify(body)+"]");
		if (res.resultCode == label.API_RESULT_CODE_SUCCESS) {
			that.header_session_id = res.sessionId;
			console.log("Login set hader_session_id:"+that.header_session_id);
			that.playerId = res.playerId;
			var town = that.createTownObj();
			town.id          = res.LivingTown.id;
			town.inquiry_id  = res.LivingTown.inquiry_id;
			town.description = res.LivingTown.description;
			town.max_entries = res.LivingTown.max_entries;
			town.entries     = res.LivingTown.entries;
			that.LivingTown  = town;
			if (that.LivingTown.id == -1) {
				that.getTownList();
			} else {
				that.entryTown(that.LivingTown);
			}
		} else {
			console.log("Login result failure.");
		}
	});
}

TestClient.prototype.getTownList = function()
{
	var that = this;
	console.log("getTownList");
	var endpoint = label.API_ENDPOINT_MULTIPLAY_GET_TOWNLIST;
	that.requestServer(REQUEST_TYPE_API, endpoint, 'GET', '', function (error, response, body) {
		var res = JSON.parse(body);
		console.log(__FILE__+":"+__LINE__+" response["+response+"] body["+JSON.stringify(body)+"]");
		if (res.resultCode == label.API_RESULT_CODE_SUCCESS) {
			// 一番盛んなタウンへ入室しようとする
			var targetTown = that.createTownObj();
			res.Towns.forEach(function(town, key) {
				console.log("res.Towns in town:"+JSON.stringify(town));
				if (targetTown.entries < town.entries) {
					targetTown.id          = town.id;
					targetTown.inquiry_id  = town.inquiry_id;
					targetTown.description = town.description;
					targetTown.max_entries = town.max_entries;
					targetTown.entries     = town.entries;
				}
			});
			console.log("getTownList selectedTown:"+JSON.stringify(targetTown));
			that.entryTown(targetTown);
		} else {
			console.log("getTownList result failure.");
		}
	});
}

TestClient.prototype.entryTown = function(selectedTown)
{
	var that = this;
	console.log("entryTown");
	var endpoint = label.API_ENDPOINT_MULTIPLAY_ENTRY_TOWN;
	var jsonObj = new Object();
	jsonObj.townId = selectedTown.inquiry_id;
	that.requestServer(REQUEST_TYPE_API, endpoint, 'POST', JSON.stringify(jsonObj), function (error, response, body) {
		var res = JSON.parse(body);
		console.log(__FILE__+":"+__LINE__+" response["+response+"] body["+JSON.stringify(body)+"]");
		if (res.resultCode == label.API_RESULT_CODE_SUCCESS) {
			that.getToken();
		} else if (res.resultCode == label.API_RESULT_CODE_REQUEST_RETRY) {
			console.log("entryTown request retry");
			that.entryTown(selectedTown);
		} else if (res.resultCode == label.API_RESULT_CODE_MULTI_SERVER_TOWN_ENTRY_FULL) {
			console.log("entryTown ENTRY FULL");
			that.getTownList();
		} else {
			console.log("entryTown result unknown");
			that.getTownList();
		}
	});
}

TestClient.prototype.getToken = function()
{
	var that = this;
	console.log("getToken");
	var endpoint = label.API_ENDPOINT_MULTIPLAY_GET_TOKEN;
	var jsonObj = new Object();
	jsonObj.serverType = 0;
	jsonObj.pvpId = '';
	that.requestServer(REQUEST_TYPE_API, endpoint, 'POST', JSON.stringify(jsonObj), function (error, response, body) {
		var res = JSON.parse(body);
		console.log(__FILE__+":"+__LINE__+" response["+response+"] body["+JSON.stringify(body)+"]");
		if (res.resultCode == label.API_RESULT_CODE_SUCCESS) {
			that.rt_server_connect_token = res.token;
			that.rt_server_connect_url   = res.url;
			that.connectRTServer();
		}
	});
}

TestClient.prototype.connectRTServer = function()
{
	var that = this;
	console.log("connectRTServer");
	var rt_url = that.rt_server_connect_url;
	rt_url = rt_url.replace("ws://", "");
	var split_url = rt_url.split("/");
	rt_url = "http://"+split_url[0];
	that.socket_options.path = "/"+split_url[1];
	//cookie.setCookies(label.COOKIE_CONNECTION_TOKEN+'='+that.rt_server_connect_token);
	that.socket_options.extraHeaders = {'Cookie':label.COOKIE_CONNECTION_TOKEN+'='+that.rt_server_connect_token};
	console.log("rt url:" + rt_url);
	console.log("rt opt:", that.socket_options);
	// that.socket = require('socket.io-client').connect(rt_url, that.socket_options);
	that.socket = require('socket.io-client')(rt_url, that.socket_options);

	var socket = that.socket;
	socket.on('open', function() {
		console.log("socket on open");
	});
	socket.on(label.SYNC_MSG_CONNECT, function() {
		console.log("socket connect");
		// request login
		socket.json.emit(label.SYNC_MSG_LOGIN, {id:socket.id, playerId: that.playerId});
	});

	socket.on(label.SYNC_MSG_LOGIN, function(recv) {
		console.log(__FILE__+":"+__LINE__+" recv["+recv+"]");
		console.log("socket disconnect");
		socket.disconnect();
	});

	socket.on('disconnect', function() {
		console.log(__FILE__+":"+__LINE__+" on disconnect");
	});
}

TestClient.prototype.requestServer = function(requestType, endpoint, method, json, callback)
{
	var requestUrl = '';
	var headers = {};
	headers['Content-Type'] = 'application/json';
	if (requestType == REQUEST_TYPE_BRIDGE) {
		requestUrl = "https://test.psg.sqex-bridge.jp/native/session";
	} else {
		requestUrl = 'http://192.168.2.92'+endpoint;
		if (this.header_session_id != '') {
			headers['X-APP-SessionId'] = this.header_session_id;
		}
		var requestHash = this.getExpectedHash(endpoint, json);
		headers['X-APP-RequestHash'] = requestHash;
	}
	console.log("headers:"+JSON.stringify(headers));
	var options = {};
	options['uri'] = requestUrl;
	options['method'] = method;
	options['headers'] = headers;
	if (json.length > 0) {
		options['body'] = json;
	}
	if (requestType == REQUEST_TYPE_BRIDGE) {
		options['gzip'] = true;
	}
	console.log("options:"+JSON.stringify(options));
	request(options, function (error, response, body) {
		if (error) {
			console.log(error);
		}
		callback(error, response, body);
	});
}

TestClient.prototype.getExpectedHash = function(uri, json)
{
	var src = this.getBaseString(this.header_session_id, uri, json);
	console.log("base string:"+src);
	var md5hex = crypto.createHash('md5').update(src, 'binary').digest('hex');
	return md5hex;
}

TestClient.prototype.getBaseString = function(session_id, requestUri, jsonString)
{
	var base_string = '';
	if (session_id != '') {
		base_string += session_id + ' ';
	}
	base_string += requestUri;
	if (jsonString != '') {
		base_string += ' ' + jsonString;
	}
	base_string += ' ' + REQUEST_HASH_SECRET;
	return base_string;
}

TestClient.prototype.outDateString = function(d){
	function pad(n){return n<10 ? '0'+n : n}
		return d.getFullYear()
			+ pad(d.getMonth()+1)
			+ pad(d.getDate())+'_'
			+ pad(d.getHours())
			+ pad(d.getMinutes())
			+ pad(d.getSeconds())
}
