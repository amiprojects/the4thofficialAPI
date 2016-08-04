<?php
require_once dirname ( __FILE__ ) . '/DbConnect.php';
class dboperation extends DbConnect {
	public $conn;
	public $db;
	
	// for Database connection //////////////////////////////////////////////////////
	function __construct() {
		// opening db connection
		$this->db = new DbConnect ();
		$this->conn = $this->db->connect ();
	}
	// ///////////////////////////////////////////////////////////////////////////////
	
	// for data inserting or ignoring into install_device table///////////////////////
	function insertDeviceID(install_device $did) {
		$response = array ();
		$this->conn->autocommit ( false );
		$sql = "INSERT ignore INTO install_device (device_id, install_date) VALUES (?,?);";
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			$stmt->bind_param ( "ss", $did->device_id, $did->install_date );
			if ($stmt->execute ()) {
				$this->conn->commit ();
				$response ["error"] = false;
				$response ["msg"] = INSERT_SUCCESS;
			} else {
				$response ["error"] = true;
				$response ["msg"] = INSERT_FAILED;
			}
		} else {
			$response ["error"] = true;
			$response ["msg"] = QUERY_EXCEPTION;
		}
		return $response;
	}
	// ///////////////////////////////////////////////////////////////////////////////
	
	// for data inserting or updating into notification_device table///////////////
	function insertNotiDevice(notification_device $notiDevice, $device_id) {
		$response = array ();
		$this->conn->autocommit ( false );
		
		$tempdv = $this->getDeviceByDeviceID ( $device_id );
		
		if (! $tempdv ['error']) {
			$tempdv1 = $this->getNotiDeviceByDeviceIDandSlug ( $tempdv ['device']->id, $notiDevice->slug );
			
			if ($tempdv1 ['error']) {
				$sql = "INSERT INTO notification_device (slug, device_id, isOn) VALUES (?,?,?);";
				$stmt = $this->conn->prepare ( $sql );
				
				if ($stmt) {
					$stmt->bind_param ( "sii", $notiDevice->slug, $tempdv ['device']->id, $notiDevice->isOn );
					if ($stmt->execute ()) {
						$this->conn->commit ();
						$response ["error"] = false;
						$response ["msg"] = INSERT_SUCCESS;
					} else {
						$response ["error"] = true;
						$response ["msg"] = INSERT_FAILED;
					}
				} else {
					$response ["error"] = true;
					$response ["msg"] = QUERY_EXCEPTION;
				}
			} else {
				$response = $this->updateNotiDevice ( $notiDevice, $device_id );
			}
		} else {
			$response = $this->updateNotiDevice ( $notiDevice, $device_id );
		}
		
		return $response;
	}
	// ///////////////////////////////////////////////////////////////////////////////
	
	// for data updating into notification_device table/////////////////////////////
	function updateNotiDevice(notification_device $noti_device, $device_id) {
		$response = array ();
		$this->conn->autocommit ( false );
		$sql = "UPDATE notification_device SET isOn=? WHERE slug=? and device_id=?;";
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			$tempdv = $this->getDeviceByDeviceID ( $device_id );
			if (! $tempdv ['error']) {
				$stmt->bind_param ( "isi", $noti_device->isOn, $noti_device->slug, $tempdv ['device']->id );
				if ($stmt->execute ()) {
					$this->conn->commit ();
					$response ["error"] = false;
					$response ["msg"] = UPDATE_SUCCESS;
				} else {
					$response ["error"] = true;
					$response ["msg"] = UPDATE_FAILED;
				}
			} else {
				$response ["error"] = true;
				$response ["msg"] = DEVICE_NOT_FOUND;
			}
		} else {
			$response ["error"] = true;
			$response ["msg"] = QUERY_EXCEPTION;
		}
		return $response;
	}
	// ///////////////////////////////////////////////////////////////////////////////
	
	/**
	 * get install device by device id
	 *
	 * @param unknown $deviceId        	
	 */
	
	// to get data from install_device table by device_id////////////////////////////////////
	function getDeviceByDeviceID($deviceId) {
		$response = array ();
		$sql = "SELECT id,device_id,install_date FROM install_device where device_id=?;";
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			$stmt->bind_param ( "s", $deviceId );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$num_rows = $stmt->num_rows;
				$inst_dvc = new install_device ();
				if ($num_rows > 0) {
					$stmt->bind_result ( $id, $device_id, $install_date );
					$stmt->fetch ();
					$inst_dvc->id = $id;
					$inst_dvc->device_id = $device_id;
					$inst_dvc->install_date = $install_date;
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ["device"] = $inst_dvc;
				} else {
					$response ["error"] = true;
					$response ["msg"] = DATA_NOT_FOUND;
				}
			} else {
				$response ["error"] = true;
				$response ["msg"] = QUERY_EXCEPTION;
			}
		} else {
			$response ["error"] = true;
			$response ["msg"] = QUERY_EXCEPTION;
		}
		return $response;
	}
	// ///////////////////////////////////////////////////////////////////////////////
	
	// to get data from notification_device table by device_id and slug////////////////////
	function getNotiDeviceByDeviceIDandSlug($deviceId, $slug) {
		$response = array ();
		$sql = "SELECT * FROM notification_device where device_id=? and slug=?;";
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			$stmt->bind_param ( "ss", $deviceId, $slug );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
				} else {
					$response ["error"] = true;
					$response ["msg"] = DATA_NOT_FOUND;
				}
			} else {
				$response ["error"] = true;
				$response ["msg"] = QUERY_EXCEPTION;
			}
		} else {
			$response ["error"] = true;
			$response ["msg"] = QUERY_EXCEPTION;
		}
		return $response;
	}
	// ///////////////////////////////////////////////////////////////////////////////
	
	// to get data from players table by team_id////////////////////////////////////
	function getPositionWisePlayersByTeamId($teamId) {
		$response = array ();
		$sql = "select id, api_id, team_id, jerseyNumber, name, position_id, position, nationality, dateOfBirth, contractUntil, imageUrl, country, height, weight, fouls_commited, fouls_drawn, goals, offsides, missed_penalties, scored_penalties, redcards, saves, shots_total, yellowcards from players where team_id=?";
		$stmt = $this->conn->prepare ( $sql );
		$temparr = array ();
		
		if ($stmt) {
			$stmt->bind_param ( "i", $teamId );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $id, $api_id, $team_id, $jerseyNumber, $name, $position_id, $position, $nationality, $dateOfBirth, $contractUntil, $imageUrl, $country, $height, $weight, $fouls_commited, $fouls_drawn, $goals, $offsides, $missed_penalties, $scored_penalties, $redcards, $saves, $shots_total, $yellowcards );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					while ( $stmt->fetch () ) {
						$player = new player ();
						$player->id = $id;
						$player->api_id = $api_id;
						$player->team_id = $team_id;
						$player->jerseyNumber = $jerseyNumber;
						$player->name = $name;
						$player->position_id = $position_id;
						$player->position = $position;
						$player->nationality = $nationality;
						$player->dateOfBirth = $dateOfBirth;
						$player->contractUntil = $contractUntil;
						$player->imageUrl = $imageUrl;
						$player->country = $country;
						$player->height = $height;
						$player->weight = $weight;
						$player->fouls_commited = $fouls_commited;
						$player->fouls_drawn = $fouls_drawn;
						$player->goals = $goals;
						$player->offsides = $offsides;
						$player->missed_penalties = $missed_penalties;
						$player->scored_penalties = $scored_penalties;
						$player->redcards = $redcards;
						$player->saves = $saves;
						$player->shots_total = $shots_total;
						$player->yellowcards = $yellowcards;
						
						if (array_key_exists ( $position, $temparr )) {
							array_push ( $temparr [$position], $player );
						} else {
							$temparr [$position] = array (
								$player 
							);
						}
					}
					
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ['Players'] = $temparr;
				} else {
					$response ["error"] = true;
					$response ["msg"] = DATA_NOT_FOUND;
				}
			} else {
				$response ["error"] = true;
				$response ["msg"] = QUERY_EXCEPTION;
			}
		} else {
			$response ["error"] = true;
			$response ["msg"] = QUERY_EXCEPTION;
		}
		return $response;
	}
	// ///////////////////////////////////////////////////////////////////////////////
	
	// to get data from team table by name////////////////////////////////////
	function getTeamByTeamName($teamName) {
		$response = array ();
		$sql = "SELECT id, api_id, name, venue, venueCity, imageUrl, logo FROM team WHERE name=?";
		$stmt = $this->conn->prepare ( $sql );
		$temparr = array ();
	
		if ($stmt) {
			$stmt->bind_param ( "s", $teamName );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $id, $api_id, $name, $venue, $venueCity, $imageUrl, $logo );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					while ( $stmt->fetch () ) {
						$team = new team();
						$team->id = $id;
						$team->api_id = $api_id;
						$team->name = $name;
						$team->venue = $venue;
						$team->venueCity = $venueCity;
						$team->imageUrl = $imageUrl;
						$team->logo = $logo;						
	
						//array_push ( $temparr, $team );
						$temparr=$team;
					}
						
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ['team'] = $temparr;
				} else {
					$response ["error"] = true;
					$response ["msg"] = DATA_NOT_FOUND;
				}
			} else {
				$response ["error"] = true;
				$response ["msg"] = QUERY_EXCEPTION;
			}
		} else {
			$response ["error"] = true;
			$response ["msg"] = QUERY_EXCEPTION;
		}
		return $response;
	}
	// ///////////////////////////////////////////////////////////////////////////////
}

// while ( $result = $stmt1->fetch () ) {
// $memory = new ami_h_memories ( $id, $couple_id, $ami_h_login_id, $description, $created_date );
// $memories [$i] = $memory;
// $i ++;
// }
?>