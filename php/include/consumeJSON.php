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
	function insertFixtures() {
		$response = array ();
		$json = file_get_contents ( API_host . 'matches/2016-07-30/2016-12-01?api_token=' . api_token . "&include=venue" );
		$obj = json_decode ( $json, true );
		
		$temp_res = array ();
		$q = 0;
		foreach ( $obj ['data'] as $key => $value ) {
			
			$fixtures = new fixtures ();
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
		
		$sql = "INSERT INTO fixtures (match_time, status, match_date, goalsHomeTeam, goalsAwayTeam, homeTeamId, awayTeamId, leagueId, venue, spectators,ht_score,ft_score,et_score,extra_minute ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?);";
		
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			
			$stmt->bind_param ( "sssiiiiisisssi", $fixtures->match_time, $fixtures->status, $fixtures->match_date, $fixtures->goalsHomeTeam, $fixtures->goalsAwayTeam, $fixtures->homeTeamId, $fixtures->awayTeamId, $fixtures->leagueId, $fixtures->venue, $fixtures->spectators, $fixtures->ht_score, $fixtures->ft_score, $fixtures->et_score, $fixtures->extra_minute );
			
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
			if (count ( $value ['lineups'] ['lineups'] )>0) {
				$player = new player ();
				$lineup = $value ['lineups'] ['lineups'];
				$lineups = $lineup [(count ( $lineup )-1)];
				
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
			}else{
				$response ['error'] = true;
				$response ['message'] = DATA_NOT_FOUND;
				return $response;
			}
		}
		$response ['error'] = false;
		$response ['result'] = $tempArr;
		return $response;
	}
}

?>