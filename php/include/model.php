<?php
class install_device {
	public $id;
	public $device_id;
	public $install_date;
}
class notification_device {
	public $id;
	public $slug;
	public $device_id;
	public $isOn;
}
class fixtures {
	public $id;
	public $match_time;
	public $status;
	public $match_date;
	public $goalsHomeTeam;
	public $goalsAwayTeam;
	public $homeTeamId;
	public $awayTeamId;
	public $leagueId;
	public $venue;
	public $spectators;
	public $extra_minute;
	public $competition_id;
	public $venue_id;
	public $ht_score;
	public $ft_score;
	public $et_score;
}
class league{
	public $id;
	public $server_id;
	public $name;
	public $league;
	public $season;
	public $is_active;
}
?>