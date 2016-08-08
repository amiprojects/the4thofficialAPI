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
	
	// to get position wise data from players table by team_id////////////////////////////////////
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
						$team = new team ();
						$team->id = $id;
						$team->api_id = $api_id;
						$team->name = $name;
						$team->venue = $venue;
						$team->venueCity = $venueCity;
						$team->imageUrl = $imageUrl;
						$team->logo = $logo;
						
						// array_push ( $temparr, $team );
						$temparr = $team;
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
	
	// to get data from league_slug table by name////////////////////////////////////
	function getLeagueSlugBySlug($slug) {
		$response = array ();
		$sql = "SELECT league_id, slug FROM league_slug WHERE slug=?";
		$stmt = $this->conn->prepare ( $sql );
		$temparr = array ();
		
		if ($stmt) {
			$stmt->bind_param ( "s", $slug );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $league_id, $slug );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					while ( $stmt->fetch () ) {
						$league_slug = new league_slug ();
						$league_slug->slug = $slug;
						$league_slug->league_id = $league_id;						
						// array_push ( $temparr, $league_slug );
						$temparr = $league_slug;
					}
					
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ['league_slug'] = $temparr;
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
	
	// to get last data from season table by leagueId////////////////////////////////////
	function getLastSeasonByleagueId($leagueId) {
		$response = array ();
		$sql = "SELECT id, api_id, is_active, league_id, name FROM season WHERE league_id=? order by id desc";
		$stmt = $this->conn->prepare ( $sql );
		$temparr = array ();
		
		if ($stmt) {
			$stmt->bind_param ( "i", $leagueId );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $id, $api_id, $is_active, $league_id, $name );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					while ( $stmt->fetch () ) {
						$season = new season ();
						$season->id = $id;
						$season->api_id = $api_id;
						$season->is_active = $is_active;
						$season->league_id = $league_id;
						$season->name = $name;
						
						array_push ( $temparr, $season );
						// $temparr=$season;
					}
					
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ['season'] = $temparr [0];
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
	
	// to get date wise data from fixture table by league_id and season_id////////////
	function getDateWiseFixturesByLeagueIdAndSeasonId($leagueId, $seasonId) {
		$response = array ();
		$sql = "SELECT id, api_id, season_id, competition_id, match_time, status, match_date, goalsHomeTeam, goalsAwayTeam, homeTeamId, awayTeamId, leagueId, venue, spectators, extra_minute, venue_id, ht_score, ft_score, et_score FROM fixtures WHERE leagueId = ? and season_id = ? order by match_date asc";
		$stmt = $this->conn->prepare ( $sql );
		$temparr = array ();
		
		if ($stmt) {
			$stmt->bind_param ( "ii", $leagueId, $seasonId );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $id, $api_id, $season_id, $competition_id, $match_time, $status, $match_date, $goalsHomeTeam, $goalsAwayTeam, $homeTeamId, $awayTeamId, $leagueId, $venue, $spectators, $extra_minute, $venue_id, $ht_score, $ft_score, $et_score );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					while ( $stmt->fetch () ) {
						$fixture = new fixtures ();
						$fixture->id = $id;
						$fixture->api_id = $api_id;
						$fixture->season_id = $season_id;
						$fixture->competition_id = $competition_id;
						$fixture->match_time = $match_time;
						$fixture->status = $status;
						$fixture->match_date = $match_date;
						$fixture->goalsHomeTeam = $goalsHomeTeam;
						$fixture->goalsAwayTeam = $goalsAwayTeam;
						$fixture->homeTeamId = $homeTeamId;
						$fixture->awayTeamId = $awayTeamId;
						$fixture->leagueId = $leagueId;
						$fixture->venue = $venue;
						$fixture->spectators = $spectators;
						$fixture->extra_minute = $extra_minute;
						$fixture->venue_id = $venue_id;
						$fixture->ht_score = $ht_score;
						$fixture->ft_score = $ft_score;
						$fixture->et_score = $et_score;
						
						$fixture->homeTeam = $this->getTeamByTeamId ( $homeTeamId ) ['team'];
						$fixture->awayTeam = $this->getTeamByTeamId ( $awayTeamId ) ['team'];
						
						if (array_key_exists ( $match_date, $temparr )) {
							array_push ( $temparr [$match_date], $fixture );
						} else {
							$temparr [$match_date] = array (
									$fixture 
							);
						}
					}
					
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ['fixtures'] = $temparr;
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
	
	// to get data from team table by teamId////////////////////////////////////
	function getTeamByTeamId($teamId) {
		$response = array ();
		$sql = "SELECT id, api_id, name, venue, venueCity, imageUrl, logo FROM team WHERE api_id=?";
		$stmt = $this->conn->prepare ( $sql );
		$temparr = array ();
		
		if ($stmt) {
			$stmt->bind_param ( "i", $teamId );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $id, $api_id, $name, $venue, $venueCity, $imageUrl, $logo );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					while ( $stmt->fetch () ) {
						$team = new team ();
						$team->id = $id;
						$team->api_id = $api_id;
						$team->name = $name;
						$team->venue = $venue;
						$team->venueCity = $venueCity;
						$team->imageUrl = $imageUrl;
						$team->logo = $logo;
						
						$temparr = $team;
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
	
	// /////////////////////////////////////////////////////////////////////////////////
	/**
	 * getting legue standings by season id
	 *
	 * @param unknown $seasonId        	
	 * @return boolean[]|string[]|unknown[][][]
	 */
	function getStandingsBySeasonId($seasonId) {
		$response = array ();
		$sql = "SELECT t.name as team_name, t.logo, st.overall_played, st.overall_goals_scored, st.points, st.goal_difference FROM leaguestandings st, team t WHERE t.api_id=st.team_id and st.season_id=? ORDER by st.position";
		$stmt = $this->conn->prepare ( $sql );
		$temparr = array ();
		$q = 0;
		
		if ($stmt) {
			$stmt->bind_param ( "i", $seasonId );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $team_name, $logo, $overall_played, $overall_goals_scored, $points, $goal_difference );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					while ( $stmt->fetch () ) {
						$temp = array ();
						$temp ['team_name'] = $team_name;
						$temp ['logo'] = $logo;
						$temp ['overall_played'] = $overall_played;
						$temp ['overall_goals_scored'] = $overall_goals_scored;
						$temp ['points'] = $points;
						$temp ['goal_difference'] = $goal_difference;
						$temparr [$q ++] = $temp;
					}
					
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ['data'] = $temparr;
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
	
	/**
	 * get fixture by date
	 * 
	 * @param unknown $date        	
	 * @return boolean[]|string[]|NULL[]|fixtures[][][]
	 */

	function getFixturesByDate($startDate,$endDate,$orderby) {
		$response = array ();
		if($orderby==1){
			$sql = "SELECT fixtures.id, fixtures.api_id, fixtures.season_id, fixtures.competition_id, fixtures.match_time, fixtures.status, fixtures.match_date, fixtures.goalsHomeTeam, fixtures.goalsAwayTeam, fixtures.homeTeamId, fixtures.awayTeamId, fixtures.leagueId, fixtures.venue, fixtures.spectators, fixtures.extra_minute, fixtures.venue_id, fixtures.ht_score, fixtures.ft_score, fixtures.et_score, league.name as league_name FROM fixtures, league WHERE league.api_id=fixtures.competition_id and match_date BETWEEN ? and ? order by match_date";
		}else{
			$sql = "SELECT fixtures.id, fixtures.api_id, fixtures.season_id, fixtures.competition_id, fixtures.match_time, fixtures.status, fixtures.match_date, fixtures.goalsHomeTeam, fixtures.goalsAwayTeam, fixtures.homeTeamId, fixtures.awayTeamId, fixtures.leagueId, fixtures.venue, fixtures.spectators, fixtures.extra_minute, fixtures.venue_id, fixtures.ht_score, fixtures.ft_score, fixtures.et_score, league.name as league_name FROM fixtures, league WHERE league.api_id=fixtures.competition_id and match_date BETWEEN ? and ? order by match_date DESC";
		}
		
		$stmt = $this->conn->prepare ( $sql );
		$temparr = array ();
		
		if ($stmt) {
			$stmt->bind_param ( "ss", $startDate,$endDate );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $id, $api_id, $season_id, $competition_id, $match_time, $status, $match_date, $goalsHomeTeam, $goalsAwayTeam, $homeTeamId, $awayTeamId, $leagueId, $venue, $spectators, $extra_minute, $venue_id, $ht_score, $ft_score, $et_score, $league_name );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					while ( $stmt->fetch () ) {
						$fixture = new fixtures ();
						$fixture->id = $id;
						$fixture->api_id = $api_id;
						$fixture->season_id = $season_id;
						$fixture->competition_id = $competition_id;
						$fixture->match_time = $match_time;
						$fixture->status = $status;
						$fixture->match_date = $match_date;
						$fixture->goalsHomeTeam = $goalsHomeTeam;
						$fixture->goalsAwayTeam = $goalsAwayTeam;
						$fixture->homeTeamId = $homeTeamId;
						$fixture->awayTeamId = $awayTeamId;
						$fixture->leagueId = $leagueId;
						$fixture->venue = $venue;
						$fixture->spectators = $spectators;
						$fixture->extra_minute = $extra_minute;
						$fixture->venue_id = $venue_id;
						$fixture->ht_score = $ht_score;
						$fixture->ft_score = $ft_score;
						$fixture->et_score = $et_score;
						$fixture->league_name = $league_name;
						
						$fixture->homeTeam = $this->getTeamByTeamId ( $homeTeamId ) ['team'];
						$fixture->awayTeam = $this->getTeamByTeamId ( $awayTeamId ) ['team'];
						
						if (array_key_exists ( $match_date, $temparr )) {
							array_push ( $temparr [$match_date], $fixture );
						} else {
							$temparr [$match_date] = array (
									$fixture 
							);
						}
					}
					
					$temparr2=$temparr;
					
					foreach ($temparr as $val=>$key){
						$temp=array();
						//echo $val;
						foreach ($temparr[$val] as $fix){
							if (array_key_exists ( $fix->league_name, $temp )) {
								array_push ( $temp [$fix->league_name], $fix );
							} else {
								$temp [$fix->league_name] = array (
										$fix
								);
							}
						}
						$temparr2[$val]=$temp;
					}
					
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ['fixtures'] = $temparr2;
				} else {
					$response ["error"] = true;
					$response ["msg"] = DATA_NOT_FOUND;
				}
			} else {
				$response ["error"] = true;
				$response ["msg"] = QUERY_EXCEPTION;
				$response ["msgdet"] = $this->conn->error;
			}
		} else {
			$response ["error"] = true;
			$response ["msg"] = QUERY_EXCEPTION;
			$response ["msgdet"] = $this->conn->error;
		}
		return $response;
	}
	
	// to get data from players table by player_id////////////////////////////////////
	function getPlayerDetailsByPlayerId($playerId) {
		$response = array ();
		$sql = "select id, api_id, team_id, jerseyNumber, name, position_id, position, nationality, dateOfBirth, contractUntil, imageUrl, country, height, weight, fouls_commited, fouls_drawn, goals, offsides, missed_penalties, scored_penalties, redcards, saves, shots_total, yellowcards from players where api_id=?";
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			$stmt->bind_param ( "i", $playerId );
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
					}
					
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ['Player'] = $player;
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
	
	// while ( $result = $stmt1->fetch () ) {
	// $memory = new ami_h_memories ( $id, $couple_id, $ami_h_login_id, $description, $created_date );
	// $memories [$i] = $memory;
	// $i ++;
	// }
}
?>