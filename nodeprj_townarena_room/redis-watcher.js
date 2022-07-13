var label = require('./labels.js');

module.exports = function(setting) {
	return new RedisWatcher(setting);
}

//
function RedisWatcher(setting)
{
	// redis pool
	this.poolRedis = require('pool-redis')(setting);
}

RedisWatcher.prototype.get = function() {
	return this.poolRedis;
}

RedisWatcher.prototype.getWatcher = function(key, expire, socket, callback)
{
	var multi  = false;
	var that   = this;
	this.poolRedis.getClient(function(client, done) {
		// console.log(__FILE__+':client.watch:'+key, socket);
		console.log(__FILE__+':client.watch:'+key);
		client.watch(key);
		
		client.get(key, function(err, val) {
			multi = client.multi();
			// console.log(__FILE__+':multi(get)', socket);
			console.log(__FILE__+':multi(get):'+key);
			
			var workAPI = {
				setex: function(val, retry, timeoutFunc, callback) {
					if ( multi ) {
						if ( val ) {
							if (expire == 0) {
								multi.set(key, val);
							} else {
								multi.setex(key, expire, val);
							}
						} else {
							multi.del(key);
						}
						
						multi.exec(function(err, replies){
							if ( err ) {
								workAPI.release();
								console.log('err('+err+') at '+__FILE__+':'+__LINE__, socket);
								callback(err);
								return;
							}
							
							done();
							done = function() {};
							
							// 競合
							if ( replies == null ) {
								workAPI.release();
								retry++;
								if ( retry >= 10 ) {
									console.log("timeout, retry="+retry, socket);
									callback('timeout');
									return;
								}
								console.log('retry='+retry, socket);
								setTimeout(function() {
									timeoutFunc(retry);
								}, 1);
							} else {
								callback(false);
							}
						});
					} else {
						callback(false);
					}
				},
				release: function() {
					if ( multi ) {
						console.log(__FILE__+':multi.discard');
						multi.discard();
					}
					console.log(__FILE__+':client.unwatch:'+key);
					client.unwatch();
					done();
				}
			}
			
			if ( err ) {
				console.log('err('+err+') at '+__FILE__+':'+__LINE__, socket);
				workAPI.release();
				callback(label.SYNC_RES_NG);
				return;
			}
			
			callback(label.SYNC_RES_OK, val, workAPI);
		});
	});
}
