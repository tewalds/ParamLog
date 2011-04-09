<?

class User {
	public $userid;
	public $active;
	public $email;
	public $admin;
	public $apikey;

	function __construct($row = array()){
		if(empty($row)){
			$this->userid = 0;
			$this->active = 0;
			$this->email  = '';
			$this->admin  = false;
			$this->apikey = "";
		}else{
			$this->userid = (int)$row['userid'];
			$this->active = (bool)$row['active'];
			$this->email  = $row['email'];
			$this->admin  = false;//$row['admin'];
			$this->apikey = $row['apikey'];
		}
	}
}

function randkey(){
	return md5(rand() . rand() . rand() . rand() . rand());
}

function createsession($email, $password, $timeout){
	global $db;
	$row = $db->pquery("SELECT * FROM users WHERE email = ? && password = ?", $email, md5($password))->fetchrow();

	if(!$row || !$row['active'])
		return new User();

	$key = randkey();
	$time = time();
	$db->pquery("INSERT INTO sessions SET userid = ?, sessionkey = ?, logintime = ?, activetime = ?, cookietime = ?, timeout = ?",
		$row['userid'], $key, $time, $time, $time, $timeout);

	setcookie("session", $key, $time + $timeout + 3600);

	return new User($row);
}

function destroysession($key){
	global $db;
	$db->pquery("DELETE FROM sessions WHERE sessionkey = ?", $key);
	setcookie("session", "", time() - 1000000, "/");
	return new User();
}

function auth($key){
	global $db;

	if($key == "")
		return new User();

	$session = $db->pquery("SELECT * FROM sessions WHERE sessionkey = ?", $key)->fetchrow();

	$time = time();
	if(!$session || $session['activetime'] + $session['timeout'] < $time)
		return destroysession($key);

	$user = $db->pquery("SELECT * FROM users WHERE userid = ?", $session['userid'])->fetchrow();

	if(!$user) // || !$user['active'])
		return destroysession($key);

	if($time - $session['cookietime'] > 1800){
		setcookie("session", $key, $time + $session['timeout'] + 3600, "/");
		$session['cookietime'] = $time;
	}

	$db->pquery("UPDATE sessions SET activetime = ?, cookietime = ? WHERE sessionkey = ?", $time, $session['cookietime'], $key);

	return new User($user);
}

function auth_api($key){
	global $db;

	if($key == "")
		return new User();

	$user = $db->pquery("SELECT * FROM users WHERE apikey = ?", $key)->fetchrow();

	return new User($user);
}

function changepassword($user, $old, $new1, $new2){
	global $db;

	if(empty($old) || empty($new1) || empty($new2))
		return echo_false("Must fill in all fields");

	if($new1 != $new2)
		return echo_false("New passwords must match");

	if(strlen($new1) < 5)
		return echo_false("New password is too short");

	$row = $db->pquery("SELECT * FROM users WHERE userid = ? && password = ?", $user->userid, md5($old))->fetchrow();

	if(!$row)
		return echo_false("Wrong old password");

	$row = $db->pquery("UPDATE users SET password = ?, apikey = ? WHERE userid = ?", md5($new1), randkey(), $user->userid)->affectedrows();

	if(!$row)
		return echo_false("Update failed.");

	return true;
}
