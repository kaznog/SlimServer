module.exports = function() {
	return new Factory();
}

//
function Factory()
{
}

// プロセス情報
Factory.prototype.createProcessInfoObject = function()
{
	return {
		version: 0,
		hostId: '???',
		working: false,
		reboot: false,
		sockets: 0,
		rooms: 0,
		aSockNum: [],
		aRoomNum: [],
		cpu: 0,
		connectionFailCount: 0,
		continuation: 0,
		uncaught: 0,
		totalCount: 0,
		pass_1: 0,
		memory: {},
	};
}

Factory.prototype.createPlayerObject = function()
{
	return {
		roomType:   -1,
		// roomSlot:   -1,
		// matchId:    -1,
		// convMatchId:'',
		// eventId:    -1,
		// maxPlayers: 0,
		playerId:   '0',
		townId:     '',
		arenaId:    '',
		// inquiryId:  0,
		name:       '???',
		level:      0,
		// multiLevel: 0,
		// titleId:    -1,
		// teamId:     '',
		// teamName:   '',
		remote:     null,
		// gameCh:     null,
		// masterId:   null,
		// masterNo:   -1,
		admin:      false,
		timerId:    0,
		timeStamp:  0,
		// cryptKey:   label.CRYPT_SYS_KEY,
		loginDate:  0,
		lastAccess: 0,
		bBeginner:  false,
		regulation: null,
		// pvpRate:    0,
		// matchDiff:  0,
		// aMatchRate: [],
		blank:      false,
		roomBusy:   false,
	};
}
