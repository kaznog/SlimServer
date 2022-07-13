var GameObject = require('./gameObject');
var fs         = require('fs');
module.exports = function(saved_data_filename) {
	return new Synchronizer(saved_data_filename);
};

function Synchronizer(saved_data_filename) {
	this._messages = [];
	this._savedGameObjects = {};
	this.savedDataPath = saved_data_filename;
}

Synchronizer.prototype.release = function() {
	this._messages = [];
	this._savedGameObjects = {};
	this._messages = null;
	this._savedGameObjects = null;
}

Synchronizer.prototype.isExistsFile = function(file_name) {
	try {
		fs.statSync(file_name);
		return true;
	} catch(err) {
		if (err.code == 'ENOENT') return false;
	}
}

Synchronizer.prototype.loadGameObjects = function(callback) {
	var self = this;
	if (self.isExistsFile(this.savedDataPath)) {
		fs.readFile(this.savedDataPath, function(err, body) {
			if (err) throw err;
			body.toString().split('\n').forEach(function(line) {
				self._saveComponent(line);
			});
			if (typeof callback === 'function') callback();
		});
	}
}

Synchronizer.prototype.saveGameObjects = function() {
	var messages = this.getSavedComponentsMessages();
	fs.writeFile(savedDataPath, messages, function(err) {
		if (err) console.error(err);
	});
}

Synchronizer.prototype.hasMessages = function() {
	return this._messages.length > 0;
}

Synchronizer.prototype.Length = function() {
	return Object.keys(this._savedGameObjects).length;
}

Synchronizer.prototype.add = function(message) {
	var message = message.toString();
	this._messages.push(message);
	this._parse(message);
}

Synchronizer.prototype.clear = function() {
	this._messages = [];
}

Synchronizer.prototype.getMessages = function() {
	var messages = this._messages.join('\n');
	this.clear();
	return messages;
}

Synchronizer.prototype.getSavedComponentsMessages = function(isImmediatelyOwned) {
	var messages = '';
	for (var id in this._savedGameObjects) {
		var gameObject = this._savedGameObjects[id];
		var components = gameObject.components;
		for (var id in components) {
			var updateComponentMessage = components[id];
			if (isImmediatelyOwned) {
				updateComponentMessage = 'o' + updateComponentMessage.slice(1);
			}
			messages += updateComponentMessage + '\n';
			// console.log("getSavedComponentsMessages add");
		}
	}
	return messages;
}

Synchronizer.prototype.getGameObjectLine = function(id, replace) {
	var line = '';
	var gameObject = this._savedGameObjects[id];
	var components = gameObject.components;
	for (var id in components) {
		var updateComponentMessage = components[id];
		if (replace != '') {
			updateComponentMessage = replace + updateComponentMessage.slice(1);
		}
		messages += updateComponentMessage + '\n';
	}
	return messages;
}

Synchronizer.prototype.deleteGameObject = function(deleteId) {
	var self = this;
	var deleteMessage = self.getGameObjectLine(deleteId, 'd');
	self.add(deleteMessage);
}

Synchronizer.prototype._parse = function(message) {
	var self = this;
	message.split('\n').forEach(function(line) {
		var command = line[0];
		switch (command) {
			case 's': self._saveComponent(line);   break;
			case 'd': self._deleteComponent(line); break;
		}
	});
}

Synchronizer.prototype._saveComponent = function(line) {
	var gameObject = new GameObject(line);
	var id = gameObject.id;
	if (id in this._savedGameObjects) {
		this._savedGameObjects[id].merge(gameObject.components);
	} else {
		this._savedGameObjects[id] = gameObject;
	}
}

Synchronizer.prototype._deleteComponent = function(line) {
	var args = line.split('\t');
	var gameObjectId = args[1];
	delete this._savedGameObjects[gameObjectId];
}


