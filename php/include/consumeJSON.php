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
		$response=array();
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
	 * @return boolean[]|boolean[][][]|string[][][]|NULL[][][]
	 */
	function insertLegue() {
		$response = array ();
		$json = file_get_contents ( API_host . 'competitions?api_token=' . api_token . "&include=currentSeason,country" );
		$obj = json_decode ( $json, true );
		$q = 0;
		$tempArr = array ();
		foreach ( $obj ['data'] as $value ) {
			$legue = new league ();
			
			$legue->is_active = $value ['active'];
			$legue->league = $value ['name'];
			$legue->name = $value ['country'] ['name'];
			$legue->season = $value ['currentSeason'] ['name'];
			$legue->server_id = $value ['id'];
			
			$tempArr [$q ++] = $this->setLegue ( $legue );
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
		
		$sql = "INSERT ignore INTO league (server_id,name, league, season, is_active) VALUES (?,?, ?, ?, ?);";
		
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			
			$stmt->bind_param ( "sssss", $legue->server_id, $legue->name, $legue->league, $legue->season, $legue->is_active );
			
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