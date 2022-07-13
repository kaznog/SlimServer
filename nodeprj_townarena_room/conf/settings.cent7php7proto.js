module.exports = {
	
	hostname : '???',
	port : -1,
	debug : true,
	auth : false,
	ssl : false,

	fps : 30,
	pi_time : 3000,	// 3 sec
	gc_time : 600000,
	timeDefference : 32400000,

	socket_io : {
		path: '/sio',
		multi_path: true,
		path_offset: 0,
	},

	redis : {
		master : {
			host: '192.168.2.92',
			port: 6379,
			password: '',
			maxConnections: 30
		},
		
		slave : {
			host: '192.168.2.92',
			port: 6379,
			password: '',
			maxConnections: 30
		},
		
		pub : {
			host: '192.168.2.92',
			port: 6379,
			password: '',
			maxConnections: 10
		},
		
		sub : {
			host: '192.168.2.92',
			port: 6379,
			password: '',
			maxConnections: 10
		},
	},

	room : {
		maxPlayers: 4,
		expire: 3600,
		hostId: '???'
	},

	amqp : {
		url: 'amqp://app:hichewL0chew@localhost:5672'
	}

};