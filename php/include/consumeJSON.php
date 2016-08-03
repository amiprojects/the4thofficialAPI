<?php
require_once dirname ( __FILE__ ) . '/DbConnect.php';
require_once dirname ( __FILE__ ) . '/model.php';
class consumeJSON extends DbConnect {
	public $conn;
	public $db;
	function __construct() {
		$this->db = new DbConnect ();
		$this->conn = $this->db->connect ();
	}
	function insertFixtures($teamId, $sessionId) {
		$response = array ();
		$json = file_get_contents ( API_host . 'teams/' . $teamId . '/season/' . $sessionId . '?api_token=' . api_token . "&include=venue" );
		$obj = json_decode ( $json, true );
		
		$temp_res = array ();
		$q = 0;
		foreach ( $obj ['matches'] ['data'] as $key => $value ) {
			
			$fixtures = new fixtures ();
			$fixtures->api_id = $value ['id'];
			$fixtures->season_id = $value ['season_id'];
			$fixtures->match_time = $value ['starting_time'];
			$fixtures->status = $value ['status'];
			$fixtures->match_date = $value ['starting_date'];
			$fixtures->goalsHomeTeam = $value ['home_score'];
			$fixtures->goalsAwayTeam = $value ['away_score'];
			$fixtures->homeTeamId = $value ['home_team_id'];
			$fixtures->awayTeamId = $value ['away_team_id'];
			$fixtures->leagueId = $value ['competition_id'];
			$fixtures->venue = $value ['venue'] ['name'];
			$fixtures->venue_id = $value ['venue'] ['id'];
			$fixtures->spectators = $value ['status'];
			$fixtures->ht_score = $value ['ht_score'];
			$fixtures->ft_score = $value ['ft_score'];
			$fixtures->et_score = $value ['et_score'];
			$fixtures->extra_minute = $value ['extra_minute'];
			
			$temp_res [$q ++] = $this->insertFixture ( $fixtures );
		}
		$response ['error'] = false;
		$response ['result'] = $temp_res;
		return $response;
	}
	
	/**
	 * adding fixture
	 *
	 * @param fixtures $fixtures        	
	 * @return boolean[]|string[]|NULL[]
	 */
	function insertFixture(fixtures $fixtures) {
		$response = array ();
		$this->conn->autocommit ( false );
		
		$sql = "INSERT ignore INTO fixtures (api_id,season_id,match_time, status, match_date, goalsHomeTeam, goalsAwayTeam, homeTeamId, awayTeamId, leagueId, venue, spectators,ht_score,ft_score,et_score,extra_minute ) VALUES (?,?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?);";
		
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			
			$stmt->bind_param ( "iisssiiiiisisssi", $fixtures->api_id, $fixtures->season_id, $fixtures->match_time, $fixtures->status, $fixtures->match_date, $fixtures->goalsHomeTeam, $fixtures->goalsAwayTeam, $fixtures->homeTeamId, $fixtures->awayTeamId, $fixtures->leagueId, $fixtures->venue, $fixtures->spectators, $fixtures->ht_score, $fixtures->ft_score, $fixtures->et_score, $fixtures->extra_minute );
			
			$result = $stmt->execute ();
			
			if ($result) {
				
				$this->conn->commit ();
				
				$response ["error"] = false;
				
				$response ["msg"] = INSERT_SUCCESS;
			} else {
				
				$response ['error'] = true;
				
				$response ['msg'] = INSERT_FAILED;
				
				$response ['msgDet'] = $this->conn->error;
			}
		} else {
			
			$response ['error'] = true;
			
			$response ['msg'] = QUERY_EXCEPTION;
		}
		return $response;
	}
	
	/**
	 * insert legues
	 *
	 * @return boolean[]|boolean[][][]|string[][][]|NULL[][][]
	 */
	function insertLegue() {
		$response = array ();
		$json = file_get_contents ( API_host . 'competitions?api_token=' . api_token . "&include=currentSeason" );
		$obj = json_decode ( $json, true );
		$q = 0;
		$tempArr = array ();
		foreach ( $obj ['data'] as $value ) {
			$legue = new league ();
			
			$legue->is_active = $value ['active'];
			$legue->name = $value ['name'];
			$legue->api_id = $value ['id'];
			$res = $this->setLegue ( $legue );
			$resp = array ();
			if (! $res ['error']) {
				$currentSeason = new season ();
				$currentSeason->api_id = $value ['currentSeason'] ['id'];
				$currentSeason->name = $value ['currentSeason'] ['name'];
				$currentSeason->league_id = $value ['currentSeason'] ['competition_id'];
				$currentSeason->is_active = $value ['currentSeason'] ['active'];
				$resp ['season'] = $this->insertSeason ( $currentSeason );
				$resp ['team'] = $this->insertTeams ( $value ['currentSeason'] ['id'] );
				$resp['standings']=$this->insertStandings($value ['currentSeason'] ['id']);
			}
			$resp ['legue'] = $res;
			$tempArr [$q ++] = $resp;
		}
		$response ['error'] = false;
		$response ['result'] = $tempArr;
		return $response;
	}
	
	/**
	 * insert legue in table
	 *
	 * @param league $legue        	
	 * @return boolean[]|string[]|NULL[]
	 */
	function setLegue(league $legue) {
		$response = array ();
		$this->conn->autocommit ( false );
		
		$sql = "INSERT ignore INTO league (api_id,name,is_active) VALUES (?,?,?);";
		
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			
			$stmt->bind_param ( "iss", $legue->api_id, $legue->name, $legue->is_active );
			
			$result = $stmt->execute ();
			
			if ($result) {
				
				$this->conn->commit ();
				
				$response ["error"] = false;
				
				$response ["msg"] = INSERT_SUCCESS;
			} else {
				
				$response ['error'] = true;
				
				$response ['msg'] = INSERT_FAILED;
				
				$response ['msgDet'] = $this->conn->error;
			}
		} else {
			
			$response ['error'] = true;
			
			$response ['msg'] = QUERY_EXCEPTION;
		}
		return $response;
	}
	
	/**
	 * insert individual season
	 *
	 * @param season $season        	
	 * @return boolean[]|string[]|NULL[]
	 */
	function insertSeason(season $season) {
		$response = array ();
		$this->conn->autocommit ( false );
		
		$sql = "INSERT ignore INTO season (api_id,name,is_active,league_id) VALUES (?,?,?,?);";
		
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			
			$stmt->bind_param ( "issi", $season->api_id, $season->name, $season->is_active, $season->league_id );
			
			$result = $stmt->execute ();
			
			if ($result) {
				
				$this->conn->commit ();
				
				$response ["error"] = false;
				
				$response ["msg"] = INSERT_SUCCESS;
			} else {
				
				$response ['error'] = true;
				
				$response ['msg'] = INSERT_FAILED;
				
				$response ['msgDet'] = $this->conn->error;
			}
		} else {
			
			$response ['error'] = true;
			
			$response ['msg'] = QUERY_EXCEPTION;
		}
		return $response;
	}
	
	/**
	 * for inserting team
	 *
	 * @param team $team        	
	 * @return boolean[]|string[]|NULL[]
	 */
	function insertTeam(team $team) {
		$response = array ();
		$this->conn->autocommit ( false );
		$sql = "INSERT ignore INTO team (api_id,name,venue,venueCity,imageUrl,logo) VALUES (?,?,?,?,?,?);";
		$stmt = $this->conn->prepare ( $sql );
		if ($stmt) {
			$stmt->bind_param ( "isssss", $team->api_id, $team->name, $team->venue, $team->venueCity, $team->imageUrl, $team->logo );
			$result = $stmt->execute ();
			if ($result) {
				$this->conn->commit ();
				$response ["error"] = false;
				$response ["msg"] = INSERT_SUCCESS;
			} else {
				$response ['error'] = true;
				$response ['msg'] = INSERT_FAILED;
			}
		} else {
			$response ['error'] = true;
			$response ['msg'] = QUERY_EXCEPTION;
		}
		return $response;
	}
	/**
	 * isert team
	 *
	 * @param unknown $seasonId        	
	 * @return boolean[]|boolean[][][][]|string[][][][]|NULL[][][][]
	 */
	function insertTeams($seasonId) {
		$response = array ();
		$json = file_get_contents ( API_host . 'teams/season/' . $seasonId . '?api_token=' . api_token . "&include=venue" );
		$obj = json_decode ( $json, true );
		$q = 0;
		$tempArr = array ();
		foreach ( $obj ['data'] as $value ) {
			$team = new team ();
			
			$team->api_id = $value ['id'];
			$team->name = $value ['name'];
			$team->logo = $value ['logo'];
			$team->venue = $value ['venue'] ['name'];
			$team->venueCity = $value ['venue'] ['city'];
			$res = $this->insertTeam ( $team );
			$resp = array ();
			if (! $res ['error']) {
				$teamSeasonMapping = new teamSeasonMapping ();
				$teamSeasonMapping->team_id = $value ['id'];
				$teamSeasonMapping->season_id = $seasonId;
				$resp ['tmsnMap'] = $this->insertTeamSeasonMapping ( $teamSeasonMapping );
				$resp ['fixture'] = $this->insertFixtures ( $value ['id'], $seasonId );
			}
			$resp ['player'] = $this->insertPlayers ( $value ['id'] );
			$resp ['team'] = $res;
			$tempArr [$q ++] = $resp;
		}
		$response ['error'] = false;
		$response ['result'] = $tempArr;
		return $response;
	}
	/**
	 * insert Team Season Mapping
	 *
	 * @param teamSeasonMapping $teamSeasonMapping        	
	 * @return boolean[]|string[]|NULL[]
	 */
	function insertTeamSeasonMapping(teamSeasonMapping $teamSeasonMapping) {
		$response = array ();
		$this->conn->autocommit ( false );
		$sql = "INSERT ignore INTO teamseasonmapping (team_id,season_id) VALUES (?,?);";
		$stmt = $this->conn->prepare ( $sql );
		if ($stmt) {
			$stmt->bind_param ( "ii", $teamSeasonMapping->team_id, $teamSeasonMapping->season_id );
			$result = $stmt->execute ();
			if ($result) {
				$this->conn->commit ();
				$response ["error"] = false;
				$response ["msg"] = INSERT_SUCCESS;
			} else {
				$response ['error'] = true;
				$response ['msg'] = INSERT_FAILED;
				$response ['msgDet'] = $this->conn->error;
			}
		} else {
			$response ['error'] = true;
			$response ['msg'] = QUERY_EXCEPTION;
		}
		return $response;
	}
	
	/**
	 * insert player
	 *
	 * @param player $player        	
	 * @return boolean[]|string[]|NULL[]
	 */
	function insertPlayer(player $player) {
		$response = array ();
		$this->conn->autocommit ( false );
		$sql = "INSERT ignore INTO players (api_id, team_id, jerseyNumber, name, position_id, position, nationality, dateOfBirth, contractUntil, imageUrl, country, height, weight, fouls_commited, fouls_drawn, goals, offsides, missed_penalties, scored_penalties, redcards, saves, shots_total, yellowcards) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
		$stmt = $this->conn->prepare ( $sql );
		if ($stmt) {
			$stmt->bind_param ( "iiisissssssssiiiiiiiiii", $player->api_id, $player->team_id, $player->jerseyNumber, $player->name, $player->position_id, $player->position, $player->nationality, $player->dateOfBirth, $player->contractUntil, $player->imageUrl, $player->country, $player->height, $player->weight, $player->fouls_commited, $player->fouls_drawn, $player->goals, $player->offsides, $player->missed_penalties, $player->scored_penalties, $player->redcards, $player->saves, $player->shots_total, $player->yellowcards );
			$result = $stmt->execute ();
			if ($result) {
				$this->conn->commit ();
				$response ["error"] = false;
				$response ["msg"] = INSERT_SUCCESS;
			} else {
				$response ['error'] = true;
				$response ['msg'] = INSERT_FAILED;
				$response ['msgDet'] = $this->conn->error;
			}
		} else {
			$response ['error'] = true;
			$response ['msg'] = QUERY_EXCEPTION;
		}
		return $response;
	}
	/**
	 * insert player of teams
	 *
	 * @param unknown $teamId        	
	 * @return boolean[]|string[]|boolean[]|string[]|boolean[][][]|string[][][]|NULL[][][]
	 */
	function insertPlayers($teamId) {
		$response = array ();
		$json = file_get_contents ( API_host . 'players/team/' . $teamId . '?api_token=' . api_token . "&include=lineups" );
		$obj = json_decode ( $json, true );
		$q = 0;
		$tempArr = array ();
		foreach ( $obj ['data'] as $value ) {
			if (count ( $value ['lineups'] ['lineups'] ) > 0) {
				$player = new player ();
				$lineup = $value ['lineups'] ['lineups'];
				$lineups = $lineup [(count ( $lineup ) - 1)];
				
				$player->api_id = $value ['id'];
				$player->team_id = $teamId;
				$player->jerseyNumber = $lineups ['shirt_number'];
				$player->name = $value ['name'];
				$player->position = $lineups ['position'];
				$player->nationality = $value ['nationality'];
				$player->dateOfBirth = $value ['birth_date'];
				$player->country = $value ['birth_place'];
				$player->height = $value ['height'];
				$player->weight = $value ['weight'];
				$player->fouls_commited = $lineups ['fouls_commited'];
				$player->fouls_drawn = $lineups ['fouls_drawn'];
				$player->goals = $lineups ['goals'];
				$player->offsides = $lineups ['offsides'];
				$player->missed_penalties = $lineups ['missed_penalties'];
				$player->scored_penalties = $lineups ['scored_penalties'];
				$player->redcards = $lineups ['redcards'];
				$player->saves = $lineups ['saves'];
				$player->shots_total = $lineups ['shots_total'];
				$player->yellowcards = $lineups ['yellowcards'];
				
				$resp ['player'] = $this->insertPlayer ( $player );
				$tempArr [$q ++] = $resp;
			} else {
				$response ['error'] = true;
				$response ['message'] = DATA_NOT_FOUND;
				return $response;
			}
		}
		$response ['error'] = false;
		$response ['result'] = $tempArr;
		return $response;
	}
	/**
	 * insert standings by season id
	 * 
	 * @param unknown $seasonId        	
	 * @return boolean[]|boolean[][][]|string[][][]|NULL[][][]
	 */
	function insertStandings($seasonId) {
		$response = array ();
		$json = file_get_contents ( API_host . 'standings/season/' . $seasonId . '?api_token=' . api_token );
		$obj = json_decode ( $json, true );
		$q = 0;
		$tempArr = array ();
		
		foreach ( $obj['data'] [0] ['standings'] ['data'] as $value ) {
			$leaguestandings = new leagueStandings ();
			
			$leaguestandings->api_id = $value ['id'];
			$leaguestandings->current_round_name = $value ['current_round_name'];
			$leaguestandings->current_round_id = $value ['current_round_id'];
			$leaguestandings->position = $value ['position'];
			$leaguestandings->points = $value ['points'];
			$leaguestandings->overall_win = $value ['overall_win'];
			$leaguestandings->overall_draw = $value ['overall_draw'];
			$leaguestandings->overall_loose = $value ['overall_loose'];
			$leaguestandings->overall_played = $value ['overall_played'];
			$leaguestandings->overall_goals_attempted = $value ['overall_goals_attempted'];
			$leaguestandings->overall_goals_scored = $value ['overall_goals_scored'];
			$leaguestandings->home_win = $value ['home_win'];
			$leaguestandings->home_draw = $value ['home_draw'];
			$leaguestandings->home_loose = $value ['home_loose'];
			$leaguestandings->home_played = $value ['home_played'];
			$leaguestandings->home_goals_attempted = $value ['home_goals_attempted'];
			$leaguestandings->home_goals_scored = $value ['home_goals_scored'];
			$leaguestandings->away_win = $value ['away_win'];
			$leaguestandings->away_draw = $value ['away_draw'];
			$leaguestandings->away_loose = $value ['away_loose'];
			$leaguestandings->away_played = $value ['away_played'];
			$leaguestandings->away_goals_attempted = $value ['away_goals_attempted'];
			$leaguestandings->away_goals_scored = $value ['away_goals_scored'];
			$leaguestandings->goal_difference = $value ['goal_difference'];
			$leaguestandings->status = $value ['status'];
			$leaguestandings->recent_form = $value ['recent_form'];
			$leaguestandings->result = $value ['result'];
			$leaguestandings->team_id = $value ['team'] ['id'];
			
			$resp ['leaguestandings'] = $this->setStandings ( $leaguestandings );
			$tempArr [$q ++] = $resp;
		}
		$response ['error'] = false;
		$response ['result'] = $tempArr;
		return $response;
	}
	
	/**
	 * insert legue standings
	 * 
	 * @param unknown $leaguestandings        	
	 * @return boolean[]|string[]|NULL[]
	 */
	function setStandings(leagueStandings $leaguestandings) {
		$response = array ();
		$this->conn->autocommit ( false );
		$sql = "INSERT ignore INTO leaguestandings (api_id, current_round_name, current_round_id, position, points, overall_win, overall_draw, overall_loose, overall_played, overall_goals_attempted, overall_goals_scored, home_win, home_draw, home_loose, home_played, home_goals_attempted, home_goals_scored, away_win, away_draw, away_loose, away_played, away_goals_attempted, away_goals_scored, goal_difference, status, recent_form, result, team_id) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
		$stmt = $this->conn->prepare ( $sql );
		if ($stmt) {
			$stmt->bind_param ( "isiiiiiiiiiiiiiiiiiiiiissssi", $leaguestandings->api_id, $leaguestandings->current_round_name, $leaguestandings->current_round_id, $leaguestandings->position, $leaguestandings->points, $leaguestandings->overall_win, $leaguestandings->overall_draw, $leaguestandings->overall_loose, $leaguestandings->overall_played, $leaguestandings->overall_goals_attempted, $leaguestandings->overall_goals_scored, $leaguestandings->home_win, $leaguestandings->home_draw, $leaguestandings->home_loose, $leaguestandings->home_played, $leaguestandings->home_goals_attempted, $leaguestandings->home_goals_scored, $leaguestandings->away_win, $leaguestandings->away_draw, $leaguestandings->away_loose, $leaguestandings->away_played, $leaguestandings->away_goals_attempted, $leaguestandings->away_goals_scored, $leaguestandings->goal_difference, $leaguestandings->status, $leaguestandings->recent_form, $leaguestandings->result, $leaguestandings->team_id );
			$result = $stmt->execute ();
			if ($result) {
				$this->conn->commit ();
				$response ["error"] = false;
				$response ["msg"] = INSERT_SUCCESS;
			} else {
				$response ['error'] = true;
				$response ['msg'] = INSERT_FAILED;
				$response ['msgDet'] = $this->conn->error;
			}
		} else {
			$response ['error'] = true;
			$response ['msg'] = QUERY_EXCEPTION;
		}
		return $response;
	}
}

?>