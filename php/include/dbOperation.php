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
	function insertNotificationsDevice($slugArr, $device_id) {
		$response = array ();
		
		
		$str = array ();
		$q = 0;
		
		$sluglst = array ();
		$sluglst = json_decode ( $slugArr, true );
		
		$this->conn->autocommit ( false );
		
		$tempdv = $this->getDeviceByDeviceID ( $device_id );
		
		if (! $tempdv ['error']) {			
			foreach ( $sluglst as $slug ) {
				$tempdv1 = $this->getNotiDeviceByDeviceIDandSlug ( $tempdv ['device']->id, $slug );
				
				$noti_device = new notification_device ();
				$noti_device->slug = $slug;
				$noti_device->isOn = 1;								
				
				if ($tempdv1 ['error']) {
					$res = $this->insertNotiDevice ( $noti_device, $device_id );
					$temp = new temp();
					$temp->slug = $slug;
					$temp->msg = $res ['msg'];
					$str [$q ++] = $temp;
				} else {
					$res = $this->updateNotiDevice ( $noti_device, $device_id );
					$temp = new temp();
					$temp->slug = $slug;
					$temp->msg = $res ['msg'];
					$str [$q ++] = $temp;
				}				
			}
		} else {
			foreach ( $sluglst as $slug ) {
				$noti_device = new notification_device ();
				$noti_device->slug = $slug;
				$noti_device->isOn = 1;
				
				$res = $this->updateNotiDevice ( $noti_device, $device_id );
				$temp = new temp();
				$temp->slug = $slug;
				$temp->msg = $res ['msg'];
				$str [$q ++] = $temp;
			}
		}
		
		if (count ( $str ) > 0) {
			$response ['error'] = false;
			$response ['msg'] = "Successfull";
			$response ['Messages'] = $str;
				
		} else {
			$response ['error'] = true;
			$response ['msg'] = "No data";
		}
		
		$slgs=implode ( "','", $sluglst );
		$ison=0;
		$sql = "UPDATE notification_device SET isOn=? WHERE device_id=? and slug NOT IN ('".$slgs."');";
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			$stmt->bind_param ( "is", $ison,$tempdv ['device']->id);
			if ($stmt->execute ()) {
				$this->conn->commit ();
				$response ['DisabledOtherNotifications'] = "successfull";
			} else {
				$response ['DisabledOtherNotifications'] = "failed";
			}
		} else {
			$response ["DisabledOtherNotifications"] = QUERY_EXCEPTION;
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
		$sql = "select id, api_id, team_id, jerseyNumber, name, position_id, position, nationality, dateOfBirth, contractUntil, imageUrl, country, height, weight, fouls_commited, fouls_drawn, goals, offsides, missed_penalties, scored_penalties, redcards, saves, shots_total, yellowcards, shots_on_goal, assists from players where team_id=?";
		$stmt = $this->conn->prepare ( $sql );
		$temparr = array ();
		
		if ($stmt) {
			$stmt->bind_param ( "i", $teamId );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $id, $api_id, $team_id, $jerseyNumber, $name, $position_id, $position, $nationality, $dateOfBirth, $contractUntil, $imageUrl, $country, $height, $weight, $fouls_commited, $fouls_drawn, $goals, $offsides, $missed_penalties, $scored_penalties, $redcards, $saves, $shots_total, $yellowcards, $shots_on_goal, $assists );
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
						$player->shots_on_goal = $shots_on_goal;
						$player->assists = $assists;
						
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
	
	// to get data from league_slug table by slug////////////////////////////////////
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
					$stmt->fetch ();
					$league_slug = new league_slug ();
					$league_slug->slug = $slug;
					$league_slug->league_id = $league_id;
					
					$temparr = $league_slug;
					
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
					$stmt->fetch ();
					$team = new team ();
					$team->id = $id;
					$team->api_id = $api_id;
					$team->name = $name;
					$team->venue = $venue;
					$team->venueCity = $venueCity;
					$team->imageUrl = $imageUrl;
					$team->logo = $logo;
					
					$temparr = $team;
					
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
	function getFixturesByDate($startDate, $endDate, $orderby, $legues, $clubs) {
		$response = array ();
		if ($legues == '0' && $clubs == '0') {
			if ($orderby == 1) {
				$sql = "SELECT fixtures.id, fixtures.api_id, fixtures.season_id, fixtures.competition_id, fixtures.match_time, fixtures.status, fixtures.match_date, fixtures.goalsHomeTeam, fixtures.goalsAwayTeam, fixtures.homeTeamId, fixtures.awayTeamId, fixtures.leagueId, fixtures.venue, fixtures.spectators, fixtures.extra_minute, fixtures.venue_id, fixtures.ht_score, fixtures.ft_score, fixtures.et_score, league.name as league_name FROM fixtures, league WHERE league.api_id=fixtures.competition_id and fixtures.match_date BETWEEN ? and ? order by match_date";
			} else {
				$sql = "SELECT fixtures.id, fixtures.api_id, fixtures.season_id, fixtures.competition_id, fixtures.match_time, fixtures.status, fixtures.match_date, fixtures.goalsHomeTeam, fixtures.goalsAwayTeam, fixtures.homeTeamId, fixtures.awayTeamId, fixtures.leagueId, fixtures.venue, fixtures.spectators, fixtures.extra_minute, fixtures.venue_id, fixtures.ht_score, fixtures.ft_score, fixtures.et_score, league.name as league_name FROM fixtures, league WHERE league.api_id=fixtures.competition_id and fixtures.match_date BETWEEN ? and ? order by match_date DESC";
			}
		} else {
			if ($legues != "" && $clubs != "") {
				if ($orderby == 1) {
					$sql = "SELECT fixtures.id, fixtures.api_id, fixtures.season_id, fixtures.competition_id, fixtures.match_time, fixtures.status, fixtures.match_date, fixtures.goalsHomeTeam, fixtures.goalsAwayTeam, fixtures.homeTeamId, fixtures.awayTeamId, fixtures.leagueId, fixtures.venue, fixtures.spectators, fixtures.extra_minute, fixtures.venue_id, fixtures.ht_score, fixtures.ft_score, fixtures.et_score, league.name as league_name FROM fixtures, league WHERE league.api_id=fixtures.competition_id and fixtures.match_date BETWEEN ? and ? and (" . $legues . " or " . $clubs . ") order by match_date";
				} else {
					$sql = "SELECT fixtures.id, fixtures.api_id, fixtures.season_id, fixtures.competition_id, fixtures.match_time, fixtures.status, fixtures.match_date, fixtures.goalsHomeTeam, fixtures.goalsAwayTeam, fixtures.homeTeamId, fixtures.awayTeamId, fixtures.leagueId, fixtures.venue, fixtures.spectators, fixtures.extra_minute, fixtures.venue_id, fixtures.ht_score, fixtures.ft_score, fixtures.et_score, league.name as league_name FROM fixtures, league WHERE league.api_id=fixtures.competition_id and fixtures.match_date BETWEEN ? and ? and (" . $legues . " or " . $clubs . ") order by match_date DESC";
				}
			} elseif ($legues != "") {
				if ($orderby == 1) {
					$sql = "SELECT fixtures.id, fixtures.api_id, fixtures.season_id, fixtures.competition_id, fixtures.match_time, fixtures.status, fixtures.match_date, fixtures.goalsHomeTeam, fixtures.goalsAwayTeam, fixtures.homeTeamId, fixtures.awayTeamId, fixtures.leagueId, fixtures.venue, fixtures.spectators, fixtures.extra_minute, fixtures.venue_id, fixtures.ht_score, fixtures.ft_score, fixtures.et_score, league.name as league_name FROM fixtures, league WHERE league.api_id=fixtures.competition_id and fixtures.match_date BETWEEN ? and ? and (" . $legues . ") order by match_date";
				} else {
					$sql = "SELECT fixtures.id, fixtures.api_id, fixtures.season_id, fixtures.competition_id, fixtures.match_time, fixtures.status, fixtures.match_date, fixtures.goalsHomeTeam, fixtures.goalsAwayTeam, fixtures.homeTeamId, fixtures.awayTeamId, fixtures.leagueId, fixtures.venue, fixtures.spectators, fixtures.extra_minute, fixtures.venue_id, fixtures.ht_score, fixtures.ft_score, fixtures.et_score, league.name as league_name FROM fixtures, league WHERE league.api_id=fixtures.competition_id and fixtures.match_date BETWEEN ? and ? and (" . $legues . ") order by match_date DESC";
				}
			} elseif ($clubs != "") {
				if ($orderby == 1) {
					$sql = "SELECT fixtures.id, fixtures.api_id, fixtures.season_id, fixtures.competition_id, fixtures.match_time, fixtures.status, fixtures.match_date, fixtures.goalsHomeTeam, fixtures.goalsAwayTeam, fixtures.homeTeamId, fixtures.awayTeamId, fixtures.leagueId, fixtures.venue, fixtures.spectators, fixtures.extra_minute, fixtures.venue_id, fixtures.ht_score, fixtures.ft_score, fixtures.et_score, league.name as league_name FROM fixtures, league WHERE league.api_id=fixtures.competition_id and fixtures.match_date BETWEEN ? and ? and (" . $clubs . ") order by match_date";
				} else {
					$sql = "SELECT fixtures.id, fixtures.api_id, fixtures.season_id, fixtures.competition_id, fixtures.match_time, fixtures.status, fixtures.match_date, fixtures.goalsHomeTeam, fixtures.goalsAwayTeam, fixtures.homeTeamId, fixtures.awayTeamId, fixtures.leagueId, fixtures.venue, fixtures.spectators, fixtures.extra_minute, fixtures.venue_id, fixtures.ht_score, fixtures.ft_score, fixtures.et_score, league.name as league_name FROM fixtures, league WHERE league.api_id=fixtures.competition_id and fixtures.match_date BETWEEN ? and ? and (" . $clubs . ") order by match_date DESC";
				}
			} else {
			}
		}
		
		$stmt = $this->conn->prepare ( $sql );
		$temparr = array ();
		
		if ($stmt) {
			$stmt->bind_param ( "ss", $startDate, $endDate );
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
						
						// $fixture->homeTeam = $this->getTeamByTeamId ( $homeTeamId ) ['team'];
						
						// $fixture->awayTeam = $this->getTeamByTeamId ( $awayTeamId ) ['team'];
						
						if ($this->getTeamByTeamId ( $homeTeamId ) ['error']) {
							$fixture->homeTeam = new team ();
						} else {
							$fixture->homeTeam = $this->getTeamByTeamId ( $homeTeamId ) ['team'];
						}
						
						if ($this->getTeamByTeamId ( $awayTeamId ) ['error']) {
							$fixture->awayTeam = new team ();
						} else {
							$fixture->awayTeam = $this->getTeamByTeamId ( $awayTeamId ) ['team'];
						}
						
						if (array_key_exists ( $match_date, $temparr )) {
							array_push ( $temparr [$match_date], $fixture );
						} else {
							$temparr [$match_date] = array (
									$fixture 
							);
						}
					}
					
					$temparr2 = $temparr;
					
					foreach ( $temparr as $val => $key ) {
						$temp = array ();
						// echo $val;
						foreach ( $temparr [$val] as $fix ) {
							if (array_key_exists ( $fix->league_name, $temp )) {
								array_push ( $temp [$fix->league_name], $fix );
							} else {
								$temp [$fix->league_name] = array (
										$fix 
								);
							}
						}
						$temparr2 [$val] = $temp;
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
		$sql = "select id, api_id, team_id, jerseyNumber, name, position_id, position, nationality, dateOfBirth, contractUntil, imageUrl, country, height, weight, fouls_commited, fouls_drawn, goals, offsides, missed_penalties, scored_penalties, redcards, saves, shots_total, yellowcards, shots_on_goal, assists from players where api_id=?";
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			$stmt->bind_param ( "i", $playerId );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $id, $api_id, $team_id, $jerseyNumber, $name, $position_id, $position, $nationality, $dateOfBirth, $contractUntil, $imageUrl, $country, $height, $weight, $fouls_commited, $fouls_drawn, $goals, $offsides, $missed_penalties, $scored_penalties, $redcards, $saves, $shots_total, $yellowcards, $shots_on_goal, $assists );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					$stmt->fetch ();
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
					$player->shots_on_goal = $shots_on_goal;
					$player->assists = $assists;
					
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
	
	// push///////////////////////////////////////////////////////////////////////////
	function sendPush($slug, $articleId, $categoryId, $title, $message) {
		// API access key from Google API's Console
		// replace API
		define ( 'API_ACCESS_KEY', 'AIzaSyCh5CzidZEWZ9Xct7f1IG14CTuurMoGQNc' );
		
		if (! $this->getNotificationDeviceBySlugAndIsOn ( $slug ) ["error"]) {
			$registrationIds = $this->getNotificationDeviceBySlugAndIsOn ( $slug ) ["allDevId"];
		} else {
			$registrationIds = array ();
		}
		
		// $registrationIds = $this->getAllInstalledDevice()["allDevId"];
		
		// $registrationIds = array (
		// $to
		// );
		
		$msg = array (
				'additionalData' => array (
						"slug" => $slug,
						"articleId" => $articleId,
						"categoryId" => $categoryId
				),
				'message' => $message,
				'title' => $title,
				'vibrate' => 1,
				'sound' => 1 
		);
		// you can also add images, additionalData
		
		$fields = array (
				'registration_ids' => $registrationIds,
				'data' => $msg 
		);
		$headers = array (
				'Authorization: key=' . API_ACCESS_KEY,
				'Content-Type: application/json' 
		);
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode ( $fields ) );
		$result = curl_exec ( $ch );
		curl_close ( $ch );
		return json_decode ( $result );
	}
	// ///////////////////////////////////////////////////////////////////////////////
	
	// to get all data from install_device table////////////////////////////////////
	function getAllInstalledDevice() {
		$response = array ();
		$sql = "SELECT id,device_id,install_date FROM install_device;";
		$stmt = $this->conn->prepare ( $sql );
		
		$temparr = array ();
		$allDevId = array ();
		
		$q = 0;
		if ($stmt) {
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $id, $device_id, $install_date );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					while ( $stmt->fetch () ) {
						$inst_dvc = new install_device ();
						$inst_dvc->id = $id;
						$inst_dvc->device_id = $device_id;
						$inst_dvc->install_date = $install_date;
						array_push ( $temparr, $inst_dvc );
						array_push ( $allDevId, $device_id );
					}
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ["device"] = $temparr;
					$response ["allDevId"] = $allDevId;
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
	
	// to get data from notification_device table by slug and isOn////////////////
	function getNotificationDeviceBySlugAndIsOn($slug) {
		$response = array ();
		$sql = "SELECT id, slug, device_id, isOn FROM notification_device WHERE slug=? and isOn=1;";
		$stmt = $this->conn->prepare ( $sql );
		
		$temparr = array ();
		$allDevId = array ();
		
		$q = 0;
		if ($stmt) {
			$stmt->bind_param ( "s", $slug );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $id, $slug, $device_id, $isOn );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					while ( $stmt->fetch () ) {
						$noti_dvc = new notification_device ();
						$noti_dvc->id = $id;
						$noti_dvc->slug = $slug;
						$noti_dvc->device_id = $device_id;
						$noti_dvc->isOn = $isOn;
						array_push ( $temparr, $noti_dvc );
						array_push ( $allDevId, $this->getDeviceByID ( $device_id ) ["device"]->device_id );
					}
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ["noti_device"] = $temparr;
					$response ["allDevId"] = $allDevId;
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
	
	// to get data from install_device table by device_id////////////////////////////////////
	function getDeviceByID($id) {
		$response = array ();
		$sql = "SELECT id,device_id,install_date FROM install_device where id=?;";
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			$stmt->bind_param ( "s", $id );
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
	
	// to get data from league_slug table by league_id////////////////////////////////////
	function getLeagueSlugByLeagueId($leagueId) {
		$response = array ();
		$sql = "SELECT league_id, slug FROM league_slug WHERE league_id=?;";
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			$stmt->bind_param ( "i", $leagueId );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $league_id, $slug );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					$stmt->fetch ();
					$league_slug = new league_slug ();
					$league_slug->slug = $slug;
					$league_slug->league_id = $league_id;
					
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ['league_slug'] = $league_slug;
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
	
	// to get data from category table by slug////////////////////////////////////
	function getCategoryBySlug($slug) {
		$response = array ();
		$sql = "SELECT categoryId, slug, name, img, jerseyImgSrc, isDepth, isLegue FROM category WHERE slug=?;";
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			$stmt->bind_param ( "s", $slug );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $categoryId, $slug, $name, $img, $jerseyImgSrc, $isDepth, $isLegue );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					$stmt->fetch ();
					$category = new category ();
					$category->categoryId = $categoryId;
					$category->slug = $slug;
					
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ["category"] = $category;
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
	 * get legue by slug name
	 *
	 * @param unknown $slug        	
	 * @return boolean[]|string[]|league[]
	 */
	function getLegueBySlugName($slug) {
		$response = array ();
		$sql = "SELECT id, api_id, name, is_active, slug FROM league WHERE slug=?;";
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			$stmt->bind_param ( "s", $slug );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $id, $api_id, $name, $is_active, $slug );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					$stmt->fetch ();
					$legue = new league ();
					$legue->id = $id;
					$legue->api_id = $api_id;
					$legue->name = $name;
					$legue->is_active = $is_active;
					$legue->slug = $slug;
					
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ["legue"] = $legue;
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
	 * get team by slug
	 *
	 * @param unknown $slug        	
	 * @return boolean[]|string[]|league[]
	 */
	function getTeamBySlugName($slug) {
		$response = array ();
		$sql = "select t.api_id,t.name,c.slug FROM category c, team t where lower(c.name) like lower(CONCAT('%',t.name,'%')) and c.slug=? LIMIT 1;";
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			$stmt->bind_param ( "s", $slug );
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $api_id, $name, $slug );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					$stmt->fetch ();
					$temp = array ();
					$temp ['api_id'] = $api_id;
					$temp ['name'] = $name;
					$temp ['slug'] = $slug;
					
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ["data"] = $temp;
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
	 * get category list from slug list
	 *
	 * @param unknown $slugArr        	
	 * @return boolean[]|string[]|NULL[]
	 */
	function getCatStrBySlugArr($slugArr) {
		$response = array ();
		$sluglst = array ();
		$sluglst = json_decode ( $slugArr, true );
		try {
			$str = array ();
			$q = 0;
			foreach ( $sluglst as $slug ) {
				$res = $this->getCategoryBySlug ( $slug );
				if (! $res ['error']) {
					$str [$q ++] = $res ['category']->categoryId;
				}
			}
			if (count ( $str ) > 0) {
				$response ['error'] = false;
				$response ['msg'] = DATA_FOUND;
				$response ['categoryString'] = implode ( ",", $str );
			} else {
				$response ['error'] = true;
				$response ['msg'] = DATA_NOT_FOUND;
			}
		} catch ( Exception $e ) {
			$response ['error'] = true;
			$response ['msg'] = $e->getMessage ();
		}
		return $response;
	}
	
	/**
	 * getting all legue
	 *
	 * @return boolean[]|string[]|category[]
	 */
	function getAllLeague() {
		$response = array ();
		$sql = "SELECT categoryId, slug, name, img, jerseyImgSrc, isDepth, isLegue FROM category WHERE isLegue=1;";
		$stmt = $this->conn->prepare ( $sql );
		
		if ($stmt) {
			if ($stmt->execute ()) {
				$stmt->store_result ();
				$stmt->bind_result ( $categoryId, $slug, $name, $img, $jerseyImgSrc, $isDepth, $isLegue );
				$num_rows = $stmt->num_rows;
				if ($num_rows > 0) {
					$q = 0;
					$temp = array ();
					while ( $stmt->fetch () ) {
						$category = new category ();
						$category->categoryId = $categoryId;
						$category->slug = $slug;
						$temp [$q ++] = $category;
					}
					
					$response ["error"] = false;
					$response ["msg"] = DATA_FOUND;
					$response ["legues"] = $temp;
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
	 * get all fixture by league and club list
	 *
	 * @param unknown $startDate        	
	 * @param unknown $endDate        	
	 * @param unknown $orderby        	
	 * @param unknown $slugArr        	
	 * @param unknown $clubs        	
	 * @return boolean[]|string[]|boolean[][]|string[][]|NULL[][]|][[][]|NULL[]
	 */
	function getFixtureByslugs($startDate, $endDate, $orderby, $slugArr, $clubs) {
		$response = array ();
		$sluglst = array ();
		$clubslst = array ();
		$sluglst = json_decode ( $slugArr, true );
		$clubslst = json_decode ( $clubs, true );
		try {
			$str = array ();
			$q = 0;
			$str1 = array ();
			$p = 0;
			
			foreach ( $sluglst as $slug ) {
				$res = $this->getLegueBySlugName ( $slug );
				if (! $res ['error']) {
					$str [$q ++] = $res ['legue']->api_id;
				}
			}
			
			foreach ( $clubslst as $slug ) {
				$res = $this->getTeamBySlugName ( $slug );
				if (! $res ['error']) {
					$str1 [$p ++] = $res ['data'] ['api_id'];
				}
			}
			
			if (count ( $str ) > 0 || count ( $str1 ) > 0) {
				if (count ( $str ) > 0) {
					$legues = "(fixtures.leagueId=" . implode ( " or fixtures.leagueId=", $str ) . ")";
				} else {
					$legues = "";
				}
				if (count ( $str1 ) > 0) {
					$clubs = "(fixtures.awayTeamId=" . implode ( " or fixtures.awayTeamId=", $str1 ) . " or fixtures.homeTeamId=" . implode ( " or fixtures.homeTeamId=", $str1 ) . ")";
				} else {
					$clubs = "";
				}
				$resp = $this->getFixturesByDate ( $startDate, $endDate, $orderby, $legues, $clubs );
				if (! $resp ['error']) {
					$response ['error'] = false;
					$response ['msg'] = DATA_FOUND;
					$response ['legueString'] = $resp;
				} else {
					$response ['error'] = true;
					$response ['msg'] = DATA_NOT_FOUND;
				}
			} else {
				$response ['error'] = true;
				$response ['msg'] = DATA_NOT_FOUND;
			}
		} catch ( Exception $e ) {
			$response ['error'] = true;
			$response ['msg'] = $e->getMessage () . $e->getLine ();
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