global.__defineGetter__('__LINE__', function () { return (new Error()).stack.split('\n')[2].split(':').reverse()[1]; });
global.__defineGetter__('__FILE__', function () { return (new Error()).stack.split('\n')[2].match(/\/([^/:]*?):/)[1]; });
var async		    = require('async');
var domain			= require('domain').create();
domain.on('error', function (e) {
    console.error("domain:", e.message);
});
var TestClient = require('./test_client.js');

// 接続クライアント数
var NumClients		= 1;
// クライアントオプション
var ClientsOpts = {
	timeout : 15000,
	'force new connection': true,				// マルチ接続
	transports : ["websocket", "xhr-polling"]
};

// 接続先リアルタイムサーバ
var ServerAddress	= [
	'ws://192.168.2.92:3030',
	'ws://192.168.2.92:3031'
];

var num = NumClients;
var execlist = [];

for (var i = 0; i < num; i++)
{
	execlist.push(
		i == 0 ?
		function(callback) {
			var no = 0;
			var tcl = new TestClient(no, ClientsOpts, ServerAddress);
			try {
				tcl.exec();
			} catch(ex) {
				console.log(ex, ' No:'+no);
			}
			setTimeout( function() {
				callback(null, no+1);
			}, Math.floor(Math.random() * 3000) + 2000);

		}
		:
		function(no, callback) {
			var tcl = new TestClient(no, ClientsOpts, ServerAddress);
			try {
				tcl.exec();
			} catch(ex) {
				console.log(ex, ' No:'+no);
			}
			setTimeout( function() {
				callback(null, no+1);
			}, Math.floor(Math.random() * 3000) + 2000);
		}
	);
}
var start = new Date();
console.log(outDateString(start) + ' Test Started.(ClientNum=' + NumClients +')');
async.waterfall(execlist);

////////////////////////////////////////
// ユーティリティ
////////////////////////////////////////
function outDateString(d){
	function pad(n){return n<10 ? '0'+n : n}
		return d.getFullYear()+'/'
			+ pad(d.getMonth()+1)+'/'
			+ pad(d.getDate())+' '
			+ pad(d.getHours())+':'
			+ pad(d.getMinutes())+':'
			+ pad(d.getSeconds())
}
