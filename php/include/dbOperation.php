<?php
require_once dirname ( __FILE__ ) . '/DbConnect.php';
class dboperation extends DbConnect {
	public $conn;
	public $db;
	function __construct() {
		// opening db connection
		$this->db = new DbConnect ();
		$this->conn = $this->db->connect ();
	}
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
					if (! $tempdv ['error']) {
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
						$response ["msg"] = DEVICE_NOT_FOUND;
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
		// return $tempdv1;
	}
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
	
	/**
	 * get install device by device id
	 *
	 * @param unknown $deviceId        	
	 */
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
}

// while ( $result = $stmt1->fetch () ) {
// $memory = new ami_h_memories ( $id, $couple_id, $ami_h_login_id, $description, $created_date );
// $memories [$i] = $memory;
// $i ++;
// }
?>