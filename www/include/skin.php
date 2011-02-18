<?

function skin($user, $body){
	global $config;
?>
<html>
	<head>
		<title><?= $config['site_name'] ?></title>

		<link rel="stylesheet" href="/static/basic.css" type="text/css" />
		<script type="text/javascript" src="/static/basic.js"></script>
	</head>
	<body>

<div>
<a href="/">Home</a> |
<a href="/games">Games</a> |
<a href="/results">Results</a> |
<a href="/players">Players</a> |
<a href="/results/recent">Recent</a> |
<a href="/logout">Logout</a>
<br><br>
</div>

<?= $body ?>

	</body>
</html>
<?
}

