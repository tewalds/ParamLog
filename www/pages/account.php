<?

function login($data, $user){
	$user = createsession($data['email'], $data['password'], ($data['longsession'] ? 86400*30 : 86400));

	if($user->userid){
		if($data['ref'])
			redirect($data['ref']);
		else
			redirect("/");
	}

	if($data['email'])
		echo "Login failed, try again";


	$ref = $data['ref'];
	$email = $data['email'];
	$longsession = $data['longsession'];
	include("templates/loginform.php");
	return true;
}

function logout($data, $user){
	destroysession($_COOKIE['session']);
	redirect("/");
}

function createuser($data, $user){
	global $db, $config;

	$email = $data['email'];
	$password = $data['password'];

	if($email && strlen($password) >=5){
		$activatekey = randkey();
		$res = $db->pquery("INSERT INTO users SET email = ?, password = ?, active = 1, activatekey = ?, apikey = ?",
			$email, md5($password), $activatekey, randkey());

		if(!$res){
			?>A user with this email already exists, either <a href="/">Login</a> or try <a href="/lostpass">password recovery</a>.<?
		}elseif($config['activation_required']){
			if(mail($email, "$config[site_name] account creation", "Welcome to $config[site_name]. Your key is: $activatekey, or activate here: http://$_SERVER[HTTP_HOST]/activate?email=$email&key=$activatekey")){
				?>An email has been sent to your email. Go to the <a href="/activate">Activation page</a> or click the link in your email.<?
				return true;
			}else{
				$db->pquery("DELETE FROM users WHERE userid = ?", $res->insertid());
				?>An error occured sending an email to your account for activation, try again.<?
			}
		}else{
			return login($data, $user);
		}
	}elseif($password && strlen($password) < 5){
		echo "Password too short, must be at least 5 characters";
	}
	include("templates/createuser.php");
	return true;
}

function activate($data, $user){
	global $db;

	$email = $data['email'];
	$key = $data['key'];

	if($email && $key){
		$succ = $db->pquery("UPDATE users SET active = 1 WHERE email = ? && activatekey = ?", $data['email'], $data['key'])->affectedrows();
	
		if($succ){
			echo "Activation successful, go <a href='/'>Login</a>";
			return true;
		}else{
			echo "Activation failed, try again:";
		}
	}
	include("templates/activate.php");
	return true;
}

function lostpassword($data, $user){
	global $db;

	$email = $data['email'];

	if($email){
		$count = $db->pquery("SELECT count(*) FROM users WHERE email = ?", $email)->fetchfield();

		if($count){
			$key = randkey();
			$db->pquery("UPDATE users SET activatekey = ? WHERE email = ?", $key, $email);

			mail($email, "Password reset", 
				"Your activation key is $key, click here to go to the password reset page: http://$_SERVER[HTTP_HOST]/resetpassword?email=$email&key=$key");
			echo "Reset password email sent";
		}else{
			echo "No user with that email, try again";
		}
	}else{
		include("templates/lostpassword.php");
	}
	return true;
}

function resetpassword($data, $user){
	global $db;

	$email = $data['email'];
	$key = $data['key'];
	$newpass = $data['newpass'];

	if($newpass && strlen($newpass) < 5){
		echo "Password is too short";
		$newpass = "";
	}

	if($email && $key && $newpass){
		$succ = $db->pquery("UPDATE users SET password = ?, apikey = ? WHERE email = ? && activatekey = ?", md5($newpass), md5(randkey()), $email, $key)->affectedrows();
		if($succ){
			echo "Password reset, go <a href='/'>Login</a>";
			return true;
		}else{
			echo "Password reset failed, try again.";
		}
	}

	include("templates/resetpassword.php");
	return true;
}

function account($input, $user){
	global $db;

?>
Your API key is: <b><?= $user->apikey ?></b><br>
Insert it into your worker configuration so it knows which account to save games for.<br>
Changing your password will change your API key.<br>

<br><br>
<?
	include("templates/changepassword.php");

	return true;
}

function changepass($data, $user){
	global $db;

	if(changepassword($user, $data['oldpass'], $data['newpass'], $data['newpass2'])){
		echo "Password changed successfully";
		return true;
	}

	include("templates/changepassword.php");
	return true;
}

