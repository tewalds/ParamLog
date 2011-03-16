<?

function skin($user, $body){
	global $config;

	$menu = array();
	$menu['Home'] = "/";
	if($user->userid){
		$menu['Games'] = "/games";
		$menu['Results'] = "/results";
		$menu['Players'] = "/players";
		$menu['Recent'] = "/results/recent";
		$menu['Logout'] = "/logout";
	}
?>
<html>
	<head>
		<title><?= $config['site_name'] ?></title>

		<link rel="stylesheet" href="/static/basic.css" type="text/css" />
		<script src="/static/jquery-1.5.1.min.js"></script>
	</head>
	<body>

<div>
<?
$first = true;
foreach($menu as $name => $url){
	if(!$first)
		echo " | ";
	$first = false;
	echo "<a href='$url'>$name</a>";
}
?>
<br><br>
</div>

<?= $body ?>

	</body>
</html>
<?
}

