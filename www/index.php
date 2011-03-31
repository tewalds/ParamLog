<?
error_reporting(E_ALL);

include("include/config.php");
include("include/lib.php");
include("include/auth.php");
include("include/router.php");

$router = new PHPRouter();

$router->addauth("none");  //skip authentication
$router->addauth("any");   //might be logged in
$router->addauth("anon");  //must not be logged in
$router->addauth("user");  //must be logged in
$router->addauth("admin"); //must be logged in and have admin priviledges
$router->addauth("api");   //must be using a valid api key

$router->add("GET", "/",     "home.php", "home", 'any', null);
$router->add("GET", "/info", "home.php", "info", 'any',  null);

$router->addprefix("GET", "/static/", "static.php", "staticcontent", 'none', null);
$router->addprefix("GET", "/images/", "static.php", "staticimages",  'none', null);

$router->add("GET", "/login",  "account.php", "login",  'anon',  array("ref" => "string", "email" => "string", "password" => "string", "longsession" => "bool"));
$router->add("POST","/login",  "account.php", "login",  'anon',  array("ref" => "string", "email" => "string", "password" => "string", "longsession" => "bool"));
$router->add("GET", "/logout", "account.php", "logout", 'user',  null);

$router->add("GET", "/createuser",    "account.php", "createuser",    'anon',  array("email" => "string", "password" => "string"));
$router->add("POST","/createuser",    "account.php", "createuser",    'anon',  array("email" => "string", "password" => "string"));
$router->add("GET", "/activate",      "account.php", "activate",      'anon',  array("email" => "string", "key" => "string"));
$router->add("POST","/activate",      "account.php", "activate",      'anon',  array("email" => "string", "key" => "string"));
$router->add("GET", "/lostpassword",  "account.php", "lostpassword",  'anon',  array("email" => "string"));
$router->add("POST","/lostpassword",  "account.php", "lostpassword",  'anon',  array("email" => "string"));
$router->add("GET", "/resetpassword", "account.php", "resetpassword", 'anon',  array("email" => "string", "key" => "string", "newpass" => "string"));
$router->add("POST","/resetpassword", "account.php", "resetpassword", 'anon',  array("email" => "string", "key" => "string", "newpass" => "string"));

$router->add("GET", "/results",        "results.php", "showresults", 'user', null);
$router->add("GET", "/results/data",   "results.php", "getdata",     'user', array("players" => "array", "baselines" => "array", "times" => "array", "sizes" => "array", "scale" => "bool"));
$router->add("GET", "/results/hosts",  "results.php", "gethosts",    'user', null);
$router->add("GET", "/recent",         "recent.php", "getrecent",    'user', null);

$router->add("GET",  "/players",        "players.php", "players_list", 'user', null);
$router->add("POST", "/players/save",   "players.php", "players_save", 'user', array("id" => "int", "type" => "int", "parent" => "int", "name" => "string", "params" => "string", "weight" => "int"));

$router->add("GET", "/api/getwork",     "api.php", "getwork",        'api', null);
$router->add("GET", "/api/lookup",      "api.php", "lookup_game_id", 'api', array("lookup" => "string"));
$router->add("POST","/api/savegame",    "api.php", "save_game",      'api', array("id" => "int", "player1" => "int", "player2" => "int", "size" => "int", "time" => "int", "lookup" => "str", "outcome1" => "int", "outcome2" => "int", "outcomeref" => "int", "host" => "str"));
$router->add("POST","/api/addmove",     "api.php", "add_move",       'api', array("gameid" => "int", "movenum" => "int", "position" => "str", "side" => "int", "value" => "float", "outcome" => "int", "timetaken" => "float", "work" => "int", "comment" => "str"));
$router->add("POST","/api/saveresult",  "api.php", "save_result",    'api', array("player1" => "int", "player2" => "int", "size" => "int", "time" => "int", "outcome" => "int"));


$route = $router->route();

if($route->auth == 'none')
	unset($_COOKIE['session']);
$user = auth(def($_COOKIE['session'], ''));
switch($route->auth){
	case 'none':
	case 'any':
		break;

	case 'anon':
		if($user->userid)
			redirect("/");
		break;

	case 'user':
		if($user->userid == 0)
			redirect("/login?ref=$_SERVER[REQUEST_URI]");
		break;

	case 'admin':
		if($user->userid == 0 || !$user->admin)
			redirect("/");
		break;

	case 'api':
		//allow normal login or using the apikey cookie
		if($user->userid == 0){
			$user = auth_api(def($_REQUEST['apikey'], ''));

			if($user->userid == 0){
				json_error("Invalid api key");
				exit;
			}
		}
		break;

	default:
		die("This route has an unknown auth type: " . $route->auth);
}


ob_start();
if($route->file)
	require("pages/" . $route->file);
$ret = call_user_func($route->function, $route->data, $user, $route->url);
$body = ob_get_clean();

if($ret){
	include('include/skin.php');
	skin($user, $body);
}else
	echo $body;

exit;
