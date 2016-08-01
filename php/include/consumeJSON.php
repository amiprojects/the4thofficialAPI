<?php
require_once dirname ( __FILE__ ) . '/DbConnect.php';
require_once dirname ( __FILE__ ) . '/model.php';
class consumeJSON extends DbConnect{
	public $conn;
	public $db;
	function __construct() {		
		$this->db = new DbConnect ();		
		$this->conn = $this->db->connect ();
	}
	
	function insertFixture(){
		/*$json = file_get_contents('https://api.soccerama.pro/v1.1/matches/2016-07-30/2016-12-01?api_token=CEO6Xe2434mXUx81C9YDkEkdokzV0JHGJ6ZCfvoPSN1FFiB5cs7DSbaByTCy');
		$obj = json_decode($json);
		return $json; */
		
		$fixtures=new fixtures();
		
		$fixtures->date="0000-00-00";
		$fixtures->status="stat";
		$fixtures->match_date="0000-00-00";
		$fixtures->goalsHomeTeam=1;
		$fixtures->goalsAwayTeam=2;
		$fixtures->homeTeamId=3;
		$fixtures->awayTeamId=4;
		$fixtures->leagueId=8;
		$fixtures->venue="avd";
		$fixtures->spectators=1234;
		
		return $this->insertFixtures($fixtures);
	}
	
	function insertFixtures(fixtures $fixtures){
			
			$response=array();
			$this->conn->autocommit ( false );
			
			$sql = "INSERT INTO fixtures (date, status, match_date, goalsHomeTeam, goalsAwayTeam, homeTeamId, awayTeamId, leagueId, venue, spectators) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

			$stmt = $this->conn->prepare ( $sql );

			if ($stmt) {

				$stmt->bind_param ("sssiiiiisi", $fixtures->date,$fixtures->status,$fixtures->match_date,$fixtures->goalsHomeTeam,$fixtures->goalsAwayTeam,$fixtures->homeTeamId,$fixtures->awayTeamId,$fixtures->leagueId,$fixtures->venue,$fixtures->spectators);

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