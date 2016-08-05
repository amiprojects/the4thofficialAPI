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
	public $api_id;
	public $season_id;
	public $competition_id;
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
	public $venue_id;
	public $ht_score;
	public $ft_score;
	public $et_score;
}
class league {
	public $id;
	public $api_id;
	public $name;
	public $is_active;
}
class season {
	public $id;
	public $api_id;
	public $league_id;
	public $name;
	public $is_active;
}
class team {
	public $id;
	public $api_id;
	public $name;
	public $venue;
	public $venueCity;
	public $imageUrl;
	public $logo;
}
class teamSeasonMapping {
	public $team_id;
	public $season_id;
}
class player {
	public $id;
	public $api_id;
	public $team_id;
	public $jerseyNumber;
	public $name;
	public $position_id;
	public $position;
	public $nationality;
	public $dateOfBirth;
	public $contractUntil;
	public $imageUrl;
	public $country;
	public $height;
	public $weight;
	public $fouls_commited;
	public $fouls_drawn;
	public $goals;
	public $offsides;
	public $missed_penalties;
	public $scored_penalties;
	public $redcards;
	public $saves;
	public $shots_total;
	public $yellowcards;
}
class leagueStandings {
	public $id;
	public $api_id;
	public $season_id;
	public $current_round_name;
	public $current_round_id;
	public $position;
	public $points;
	public $overall_win;
	public $overall_draw;
	public $overall_loose;
	public $overall_played;
	public $overall_goals_attempted;
	public $overall_goals_scored;
	public $home_win;
	public $home_draw;
	public $home_loose;
	public $home_played;
	public $home_goals_attempted;
	public $home_goals_scored;
	public $away_win;
	public $away_draw;
	public $away_loose;
	public $away_played;
	public $away_goals_attempted;
	public $away_goals_scored;
	public $goal_difference;
	public $status;
	public $recent_form;
	public $result;
	public $team_id;
}
class league_slug {
	public $slug;
	public $league_id;
}
?>