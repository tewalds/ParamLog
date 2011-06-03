<?

function home($data, $user){
?>
<table><tr><td valign="top">

<h1>Welcome to ParamLog</h1>

ParamLog is a Distributed Tournament Runner, Game Logging, Game Analysis and Parameter Tuning Engine for Two Player Games.
The code is available at <a href="https://github.com/tewalds/ParamLog">https://github.com/tewalds/ParamLog</a>.<br>
<br>
Setup players and tests on the web interface, and start up worker processes on arbitrarily many machines
which all run the tests and report back results. View the aggregated results in a variety of ways, and download
individual games for review. Track progress over time, and with different parameters.

</td><td>
<?
	if($user->userid == 0){
		$email = "";
		$key = "";
		$longsession = true;
		$ref = "/";
		include("templates/loginform.php");
		echo "<br>";
		include("templates/createuser.php");
	}
?>
</td></tr></table>
<?
	return true;
}

function info($data, $user){
	phpinfo();

	return false;
}

