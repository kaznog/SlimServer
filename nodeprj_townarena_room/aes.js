// 暗号化ライブラリ
var crypto = require('crypto');

//
exports.decryptAES256 = function(password, enc)
{
	var pass	= new Buffer(password, 'utf8');
	var data	= new Buffer(enc, 'base64');
	var salt	= data.slice(0,32);
	var ebin	= data.slice(32);
	var salted	= new Buffer(0);

	var pwd 	= new Buffer(crypto.createHash('sha256').update(pass, 'binary').digest('hex'), 'hex');
	while ( salted.length < 64) {
		var hash	= Buffer.concat([Buffer.concat([salted, pwd]), salt]);
		salted		= Buffer.concat([salted, new Buffer(crypto.createHash('sha256').update(hash, 'binary').digest('hex'), 'hex')]);
	}

	var key = salted.slice(0,32);
	var iv  = salted.slice(32,48);

//	console.log("salted="+salted.toString('base64'));
//	console.log("key="+key.toString('base64'));
//	console.log("iv="+iv.toString('base64'));

	var decipher	= crypto.createDecipheriv('aes-256-cbc', key, iv);
	var content		= decipher.update(ebin, 'binary', 'utf8');
	content += decipher.final('utf8');

	return content;
}

//
exports.encryptAES256 = function(password, src) {

	var pass	= new Buffer(password, 'utf8');
//	var salt	= new Buffer(crypto.createHash('sha256').update('c7af9f04a34cbbffa9013b0f8587fcdc', 'hex').digest('hex'), 'hex')
	var salt	= crypto.randomBytes(32);
	var pwd 	= new Buffer(crypto.createHash('sha256').update(pass, 'binary').digest('hex'), 'hex');

	var salted	= new Buffer(0);
	while ( salted.length < 64) {
		var hash	= Buffer.concat([Buffer.concat([salted, pwd]), salt]);
		salted		= Buffer.concat([salted, new Buffer(crypto.createHash('sha256').update(hash, 'binary').digest('hex'), 'hex')]);
	}

	var key = salted.slice(0,32);
	var iv  = salted.slice(32,48);

//	console.log("salted="+salted.toString('base64'));
//	console.log("key="+key.toString('base64'));
//	console.log("iv="+iv.toString('base64'));

	var cipher	= crypto.createCipheriv('aes-256-cbc', key, iv);
	var content	= cipher.update(src, 'binary', 'hex');
	content += cipher.final('hex');

	return Buffer.concat([salt, new Buffer(content, 'hex')]).toString('base64');
}

//
exports.md5 = function(str) {
    var md5sum = crypto.createHash('md5')
    md5sum.update(str);
    return md5sum.digest('hex');
}
