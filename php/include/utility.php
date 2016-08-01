<?php
require_once dirname ( __FILE__ ) . '/DbConnect.php';
class utility extends DbConnect {
	public $conn;
	public $db;
	function __construct() {
		
		// opening db connection
		$this->db = new DbConnect ();
		$this->conn = $this->db->connect ();
	}
	public function isMobileExist($mobile, $table) {
		$response = array ();
		$sql = "SELECT * FROM " . $table . "  WHERE mobile = ?";
		$stmt = $this->conn->prepare ( $sql );
		if ($stmt) {
			$stmt->bind_param ( "s", $mobile );
			$stmt->execute ();
			$stmt->store_result ();
			$num_rows = $stmt->num_rows;
			
			if ($num_rows > 0) {
				$response ["error"] = false;
				$response ["msg"] = USER_RECORD_FOUND;
			} else {
				$response ["error"] = true;
				$response ["msg"] = USER_DOES_NOT_EXIST;
			}
		} else {
			$response ["error"] = true;
			$response ["msg"] = QUERY_EXCEPTION;
		}
		
		return $response;
	}
	public function isEmailExist($email, $table) {
		$response = array ();
		$sql = "SELECT * FROM " . $table . "  WHERE email = ?";
		$stmt = $this->conn->prepare ( $sql );
		if ($stmt) {
			$stmt->bind_param ( "s", $email );
			$stmt->execute ();
			$stmt->store_result ();
			$num_rows = $stmt->num_rows;
			
			if ($num_rows > 0) {
				$response ["error"] = false;
				$response ["msg"] = USER_RECORD_FOUND;
			} else {
				$response ["error"] = true;
				$response ["msg"] = USER_DOES_NOT_EXIST;
			}
		} else {
			$response ["error"] = true;
			$response ["msg"] = QUERY_EXCEPTION;
		}
		return $response;
	}
	public function isUserNameExist($user_name, $table) {
		$response = array ();
		$sql = "SELECT * FROM " . $table . "  WHERE user_name = ?";
		$stmt = $this->conn->prepare ( $sql );
		if ($stmt) {
			$stmt->bind_param ( "s", $user_name );
			$stmt->execute ();
			$stmt->store_result ();
			$num_rows = $stmt->num_rows;
			
			if ($num_rows > 0) {
				$response ["error"] = false;
				$response ["msg"] = USER_RECORD_FOUND;
			} else {
				$response ["error"] = true;
				$response ["msg"] = USER_DOES_NOT_EXIST;
			}
		} else {
			$response ["error"] = true;
			$response ["msg"] = QUERY_EXCEPTION;
		}
		return $response;
	}
	
	/**
	 *
	 * @param unknown $api_key        	
	 */
	public function isValidApiKey($api_key) {
		try {
			$is_active = 1;
			if ($stmt = $this->conn->prepare ( "SELECT * from ami_h_login WHERE api_key = ? AND is_active = ?" )) {
				$stmt->bind_param ( "si", $api_key, $is_active );
				$stmt->execute ();
				$stmt->store_result ();
				$num_rows = $stmt->num_rows;
				$stmt->close ();
				return $num_rows > 0;
			} else {
				return NULL;
			}
		} catch ( Exception $e ) {
			echo $e->getMessage ();
		}
	}
	
	/**
	 * get login by api key
	 *
	 * @param unknown $api_key        	
	 */
	public function getLoginIdByAPIkey($api_key) {
		try {
			$is_active = 1;
			if ($stmt = $this->conn->prepare ( "SELECT l.id FROM ami_h_login l WHERE l.is_active = ? and l.api_key = ?" )) {
				$stmt->bind_param ( "is", $is_active, $api_key );
				if ($stmt->execute ()) {
					$stmt->bind_result ( $loginId );
					$stmt->fetch ();
					$stmt->close ();
					return $loginId;
				} else {
					return NULL;
				}
			} else {
				return NULL;
			}
		} catch ( Exception $e ) {
			echo $e->getMessage ();
		}
	}
	
	/**
	 * get all login details by couple id
	 *
	 * @param unknown $couple_id        	
	 */
	public function getAllLoginDetailsByCoupleId($couple_id) {
		$response = array ();
		$logins = array ();
		$is_active = 1;
		$sql1 = "SELECT id,full_name,email,email,couple_id,created_date,mobile,api_key,device_id FROM ami_h_login where  is_active = ? and couple_id = ?";
		$stmt1 = $this->conn->prepare ( $sql1 );
		if ($stmt1) {
			$stmt1->bind_param ( "ii", $is_active, $couple_id );
			$stmt1->execute ();
			$stmt1->store_result ();
			$num_rows1 = $stmt1->num_rows;
			if ($num_rows1 > 0) {
				$stmt1->bind_result ( $id, $full_name, $email, $couple_id, $email, $created_date, $mobile, $api_key, $device_id );
				$q = 0;
				while ( $stmt1->fetch () ) {
					$logindetails = new login ( $full_name, $email, $is_active, $created_date, $mobile, $device_id );
					$logindetails->id = $id;
					$logindetails->couple_id = $couple_id;
					$logins [$q] = $logindetails;
					$q ++;
				}
				
				$response ['loginDetails'] = $logins;
				$response ["error"] = false;
				$response ["msg"] = DATA_FOUND;
			} else {
				$response ["error"] = true;
				$response ["msg"] = DATA_DOES_NOT_EXIST;
			}
		} else {
			$response ["error"] = true;
			$response ["msg"] = QUERY_EXCEPTION;
		}
		return $response;
	}
	function sendNotificationToAllLogin($couple_id, $title, $message, $page) {
		$response = array ();
		$result = array ();
		$to = array ();
		
		$loginDetails = $this->getAllLoginDetailsByCoupleId ( $couple_id );
		
		if ($loginDetails ["error"]) {
			$response ["error"] = true;
			$response ["msg"] = NO_LOGIN;
		} else {
			$response ["error"] = false;
			$response ["msg"] = NO_ERROR;
			$q = 0;
			foreach ( $loginDetails ['loginDetails'] as $logins ) {
				$to [$q] = $logins->device_id;
				// $result [$q] = $this->sendPush ( $logins->device_id, $title, $message );
				$q ++;
			}
			$response ["result"] = $this->sendPush ( $to, $title, $message, $page );
		}
		return $response;
	}
	
	/**
	 * for sending push notification
	 *
	 * @param unknown $to        	
	 * @param unknown $title        	
	 * @param unknown $message        	
	 */
	function sendPush($to, $title, $message, $page) {
		/*
		 * $registrationIds = array (
		 * $to
		 * );
		 */
		$registrationIds = $to;
		$msg = array (
				'message' => $message,
				'title' => $title,
				'page' => $page,
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
		return $result;
	}
	
	/**
	 * Generating random Unique MD5 String for user Api key
	 */
	public function generateApiKey() {
		return md5 ( uniqid ( rand (), true ) );
	}
	function generateString() {
		$string = '123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
		$string_shuffled = str_shuffle ( $string );
		$str = substr ( $string_shuffled, 1, 8 );
		return $str;
	}
	function generateOTP() {
		$string = '0123456789';
		$string_shuffled = str_shuffle ( $string );
		$str = substr ( $string_shuffled, 1, 6 );
		return $str;
	}
	function generateWeddingCode() {
		$string = '0123456789';
		$string_shuffled = str_shuffle ( $string );
		$str = substr ( $string_shuffled, 1, 4 );
		return $str;
	}
	function generateforgotpasswordkey() {
		$string = '123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
		$string_shuffled = str_shuffle ( $string );
		$str = substr ( $string_shuffled, 1, 8 );
		return $str;
	}
	public function __destruct() {
		// close the database connection
		$this->db->closeconnection ();
	}
}

?>