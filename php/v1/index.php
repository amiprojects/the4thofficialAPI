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

// for testing///////////////////////////////////////////////////////////////////
$app->post ( '/test', function () use ($app) {
	$response = array ();
	
	$myArray = array ();
	$myArray1 = array ();
	$myArray2 = array ();
	
	$noti_device = new notification_device ();
	$noti_device->id = 1;
	$noti_device->slug = 'dfsdf';
	$noti_device->device_id = 'dffsafd';
	$noti_device->isOn = 1;
	
	$myArray [0] = $noti_device;
	$myArray [1] = $noti_device;
	
	$myArray1 ["one"] = array (
			$noti_device 
	);
	array_push ( $myArray1 ["one"], $noti_device );
	$myArray1 ["two"] = array (
			$noti_device 
	);
	array_push ( $myArray1 ["two"], $noti_device );
	
	$myArray2 [0] = $myArray1;
	
	$response ["noti_dev"] = $noti_device;
	$response ["my_array"] = $myArray;
	$response ["my_array1"] = $myArray1;
	$response ["my_array2"] = $myArray2;
	echoRespnse ( 201, $response );
} );
// ///////////////////////////////////////////////////////////////////////////////

// for data inserting or ignoring into install_device table///////////////////////
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
// ///////////////////////////////////////////////////////////////////////////////

// for data inserting or updating into notification_device table///////////////
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
	// $noti_device->device_id = $device_id;
	$noti_device->isOn = $isOn;
	
	$response = $obj->insertNotiDevice ( $noti_device, $device_id );
	echoRespnse ( 201, $response );
} );
// ///////////////////////////////////////////////////////////////////////////////

// for data updating into notification_device table/////////////////////////////
$app->post ( '/update_noti', function () use ($app) {
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
	// $noti_device->device_id = $device_id;
	$noti_device->isOn = $isOn;
	
	$response = $obj->updateNotiDevice ( $noti_device, $device_id );
	echoRespnse ( 201, $response );
} );
// ///////////////////////////////////////////////////////////////////////////////

// to get position wise data from players table by team_id////////////////////////
$app->get ( '/players/team/:id', function ($id) use ($app) {
	$obj = new dboperation ();
	echoRespnse ( 202, $obj->getPositionWisePlayersByTeamId ( $id ) );
} );
// ///////////////////////////////////////////////////////////////////////////////

// to get data from team table by name////////////////////////////////////
$app->get ( '/team/:name', function ($name) use ($app) {
	$obj = new dboperation ();
	echoRespnse ( 202, $obj->getTeamByTeamName ( $name ) );
} );
// ///////////////////////////////////////////////////////////////////////////////

// to get position wise data from players table by team_name/////////////////////
// $app->get ( '/playersByTeamName/:teamName', function ($teamName) use ($app) {
// 	$obj = new dboperation ();
// 	$teamId = $obj->getTeamByTeamName ( $teamName )['team']->api_id;
// 	echoRespnse ( 202, $obj->getPositionWisePlayersByTeamId ( $teamId ) );
// } );
// //////////////////////////////////////////////////////////////////////////////

// to get data from league_slug table by slug////////////////////////////////////
$app->get ( '/league_slug/slug/:slug', function ($slug) use ($app) {
	$obj = new dboperation ();
	echoRespnse ( 202, $obj->getLeagueSlugBySlug ( $slug ) );
} );
// ///////////////////////////////////////////////////////////////////////////////

// to get last data from season table by leagueId////////////////////////////////////
$app->get ( '/season/league/:id', function ($id) use ($app) {
	$obj = new dboperation ();
	echoRespnse ( 202, $obj->getLastSeasonByleagueId ( $id ) );
} );
// ///////////////////////////////////////////////////////////////////////////////

// to get date wise data from fixture table by league_id and season_id////////////
$app->get ( '/fixture/:leagueId/:seasonId', function ($leagueId, $seasonId) use ($app) {
	$obj = new dboperation ();
	echoRespnse ( 202, $obj->getDateWiseFixturesByLeagueIdAndSeasonId ( $leagueId, $seasonId ) );
} );
// //////////////////////////////////////////////////////////////////////////////

// to get date wise data from fixture table by league's slug/////////////////////
$app->get ( '/fixture/:leagueSlug', function ($leagueSlug) use ($app) {
	$obj = new dboperation ();
	$leagueId = $obj->getLeagueSlugBySlug ( $leagueSlug ) ['league_slug']->league_id;
	$seasonId = $obj->getLastSeasonByleagueId ( $leagueId ) ['season']->api_id;
	echoRespnse ( 202, $obj->getDateWiseFixturesByLeagueIdAndSeasonId ( $leagueId, $seasonId ) );
} );
// //////////////////////////////////////////////////////////////////////////////

/**
 * *******************API for match details************************
 */
/**
 * storing fixture
 */
$app->get ( '/getAPIKey', function () use ($app) {
	$response = array ();
	$response = array (
			"API_Key" => api_token 
	);
	echoRespnse ( 201, $response );
} );

/**
 * add data in legue table
 */
$app->post ( '/legue', function () use ($app) {
	$response = array ();
	$obj = new consumeJSON ();
	$response = $obj->insertLegue ();
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
	
	$app->contentType ( 'application/json; charset=utf-8' );
	$app->response->headers->set ( 'Access-Control-Allow-Origin', '*' );
	echo json_encode ( $response );
}

$app->run ();

?>