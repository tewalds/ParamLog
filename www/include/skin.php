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
		<script type="text/javascript" src="/static/basic.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.10/jquery-ui.min.js"></script>
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

