<?php
require_once '../include/Config.php';

require_once '../include/dbOperation.php';
require_once '../include/model.php';
require_once '../include/consumeJSON.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader ();

$app = new \Slim\Slim ();

date_default_timezone_set ( 'Asia/Kolkata' );
global $date;
$date = date ( "Y-m-d" );

$app->post ( '/deviceid', function () use ($app) {
	global $date;
	$response = array ();
	
	verifyRequiredParams ( array (
			'device_id' 
	) );
	
	$deviceId = $app->request->post ( 'device_id' );
	
	$obj = new dboperation ();
	$device = new install_device ();
	$device->device_id = $deviceId;
	$device->install_date = $date;
	
	$response = $obj->insertDeviceID ( $device );
	echoRespnse ( 201, $response );
} );

$app->post ( '/insert_noti', function () use ($app) {
	$response = array ();
	
	verifyRequiredParams ( array (
			'slug',
			'device_id',
			'isOn' 
	) );
	
	$slug = $app->request->post ( 'slug' );
	$device_id = $app->request->post ( 'device_id' );
	$isOn = $app->request->post ( 'isOn' );
	
	$obj = new dboperation ();
	$noti_device = new notification_device ();
	$noti_device->slug = $slug;
	$noti_device->device_id = $device_id;
	$noti_device->isOn = $isOn;
	
	$response = $obj->insertNotiDevice ( $noti_device );
	echoRespnse ( 201, $response );
} );

$app->post ( '/fixture', function () use ($app) {
	$response = array ();
	$obj = new consumeJSON ();
	$response = $obj->insertFixture ();
	echoRespnse ( 201, $response );
} );

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
	$error = false;
	$error_fields = "";
	$request_params = array ();
	$request_params = $_REQUEST;
	
	// Handling PUT request params
	
	if ($_SERVER ['REQUEST_METHOD'] == 'PUT') {
		$app = \Slim\Slim::getInstance ();
		parse_str ( $app->request ()->getBody (), $request_params );
	}
	
	foreach ( $required_fields as $field ) {
		if (! isset ( $request_params [$field] ) || strlen ( trim ( $request_params [$field] ) ) <= 0) {
			$error = true;
			$error_fields .= constant ( strtoupper ( $field ) ) . ', ';
		}
	}
	
	if ($error) {
		// Required field(s) are missing or empty
		
		// echo error json and stop the app
		
		$response = array ();
		$app = \Slim\Slim::getInstance ();
		$response ["error"] = true;
		$response ["message"] = substr ( $error_fields, 0, - 2 ) . ' required';
		echoRespnse ( 400, $response );
		$app->stop ();
	}
}

/**
 *
 * Echoing json response to client
 *
 *
 *
 * @param String $status_code
 *        	Http response code
 *        	
 * @param Int $response
 *        	Json response
 *        	
 */
function echoRespnse($status_code, $response) {
	$app = \Slim\Slim::getInstance ();
	
	// Http response code
	
	$app->status ( $status_code );
	
	// setting response content type to json
	
	$app->contentType ( 'application/json; utf8' );
	$app->response->headers->set ( 'Access-Control-Allow-Origin', '*' );
	echo json_encode ( $response );
}

$app->run ();

?>