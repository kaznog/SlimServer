<?php
namespace App\Models;

class BattleField
{
	public static function create($playersIdentity, $field_type)
	{
		list($msec, $now) = explode(' ', microtime());
		$inquiryId = md5('#ROOM#'.$playersIdentity->id.'#'.$now.'#'.$msec);
		$battle_field = ClusterORM::for_table(battle_fields)
		->create(
			[
				'inquiryId' => $inquiryId,
				'regularion_id' => 0,				// とりあえず
				'status' => MultiPlayService::BATTLE_STATUS_NONE,
				'max_entries' => 5
			]
		)->set_expr('created_at', 'NOW()');
		return $battle_field;
	}
}