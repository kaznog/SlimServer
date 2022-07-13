<?php
return [
    'app_versions'                          => ['cluster_name' => 'master_data'],
	'players_identities'                    => ['cluster_name' => 'players_identity'],
	'players'                               => ['cluster_name' => 'player', 'shard_key_column' => 'id'],
	'maintenances'                          => ['cluster_name' => 'master_data'],
	'info_messages'                         => ['cluster_name' => 'master_data'],
	'login_bonuses'                         => ['cluster_name' => 'master_data'],
	'notices'                               => ['cluster_name' => 'master_data'],
	'players_social_activities'             => ['cluster_name' => 'player', 'shard_key_column' => 'player_id'],
	'players_daily_activities'              => ['cluster_name' => 'player', 'shard_key_column' => 'player_id'],
	'mails'                                 => ['cluster_name' => 'player', 'shard_key_column' => 'player_id'],
	'towns'                                 => ['cluster_name' => 'master_data'],
	'town_entry_reserves'                   => ['cluster_name' => 'players_identity']
];
?>