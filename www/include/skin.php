<?

function skin($user, $body){
	global $config;

	$menu = array();
	$menu['Home'] = "/";
	if($user->userid){
		$menu['Games'] = "/games";
		$menu['Results'] = "/results";
		$menu['Players'] = "/players";
		$menu['Recent'] = "/recent";
		$menu['Logout'] = "/logout";
	}
?>
<html>
	<head>
		<title><?= $config['site_name'] ?></title>
		<link href="/static/basic.css" rel="stylesheet" type="text/css" />
		<link href="/static/jquery.jqplot.min.css" rel="stylesheet" type="text/css" />

		<script src="/static/jquery-1.5.1.js"></script>
		<!--[if IE]><script src="/static/excanvas.min.js" language="javascript" type="text/javascript"></script><![endif]-->
		<script src="/static/jqplot/jquery.jqplot.js" language="javascript" type="text/javascript"></script>
		<script src="/static/jqplot/plugins/jqplot.categoryAxisRenderer.js" language="javascript" type="text/javascript"></script>
		<script src="/static/jqplot/plugins/jqplot.highlighter.js" language="javascript" type="text/javascript"></script>
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

