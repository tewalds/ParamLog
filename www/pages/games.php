<?

function getgames($input, $user){
	global $db;

	$data = $db->pquery("SELECT * FROM games WHERE userid = ? && timestamp > UNIX_TIMESTAMP()-3600 ORDER by timestamp", $user->userid)->fetchrowset();

	$players = $db->pquery("SELECT id, name, parent FROM players WHERE userid = ?", $user->userid)->fetchrowset('id');
	$times = $db->pquery("SELECT id, name FROM times WHERE userid = ?", $user->userid)->fetchfieldset();
	$sizes = $db->pquery("SELECT id, name FROM sizes WHERE userid = ?", $user->userid)->fetchfieldset();

	$outcomes = array('unknown', 'Player 1', 'Player 2', 'Draw');
?>
	<table width="100%">
	<tr>
		<th></th>
		<th>Player 1</th>
		<th>Player 2</th>
		<th>Size</th>
		<th>Time limit</th>
		<th>Moves</th>
		<th>Winner</th>
		<th>Host</th>
	</tr>

<?	foreach($data as $row){ ?>
		<tr class="l">
			<td><a href="/games/show?id=<?= $row['id'] ?>">Show</a></td>
			<td><?= playername($row['player1'], $players) ?></td>
			<td><?= playername($row['player2'], $players) ?></td>
			<td><?= $sizes[$row['size']] ?></td>
			<td><?= $times[$row['time']] ?></td>
			<td><?= $row['nummoves'] ?></td>
			<td><?= $outcomes[($row['outcomeref'] ? $row['outcomeref'] : ($row['outcome1'] == $row['outcome2'] ? $row['outcome1'] : 0))] ?></td>
			<td><?= (strpos($row['host'], '.') ? substr($row['host'], 0, strpos($row['host'], '.')) : $row['host']) ?></td>
		</tr>
<?	} ?>
	</table>

<?
	return true;
}


function showgame($input, $user){
	global $db;

	$game = $db->pquery("SELECT * FROM games WHERE userid = ? && id = ?", $user->userid, $input['id'])->fetchrow();
	$moves = $db->pquery("SELECT * FROM moves WHERE userid = ? && gameid = ? ORDER BY movenum", $user->userid, $input['id'])->fetchrowset();

	$players = $db->pquery("SELECT id, name, parent FROM players WHERE userid = ?", $user->userid)->fetchrowset('id');
	$times = $db->pquery("SELECT id, name FROM times WHERE userid = ?", $user->userid)->fetchfieldset();
	$sizes = $db->pquery("SELECT id, name FROM sizes WHERE userid = ?", $user->userid)->fetchfieldset();

?>

<table>
	<tr class='l'><th width=200>Player 1</th><td><?= playername($game['player1'], $players) ?></td></tr>
	<tr class='l'><th>Player 2</th><td><?= playername($game['player2'], $players) ?></td></tr>
	<tr class='l'><th>Boardsize</th><td><?= $sizes[$game['size']] ?></td></tr>
	<tr class='l'><th>Time Limit</th><td><?= $times[$game['time']] ?></td></tr>
	<tr class='l'><th>Number of Moves</th><td><?= $game['nummoves'] ?></td></tr>
	<tr class='l'><th>Outcome Player 1</th><td><?= $game['outcome1'] ?></td></tr>
	<tr class='l'><th>Outcome Player 2</th><td><?= $game['outcome2'] ?></td></tr>
	<tr class='l'><th>Outcome Referee</th><td><?= $game['outcomeref'] ?></td></tr>
	<tr class='l'><th>Player 1 Version</th><td><?= $game['version1'] ?></td></tr>
	<tr class='l'><th>Player 2 Version</th><td><?= $game['version2'] ?></td></tr>
	<tr class='l'><th>Host</th><td><?= $game['host'] ?></td></tr>
	<tr class='l'><th>Lookup name</th><td><?= $game['lookup'] ?></td></tr>
	<tr class='l'><th>Move string</th><td><? foreach($moves as $move) echo $move['position'] . " "	?></td></tr>
</table>

<table>
	<tr>
		<th>Num</th>
		<th>Move</th>
		<th>Player</th>
		<th>Value</th>
		<th>Outcome</th>
		<th>Time Taken</th>
		<th>Simulations</th>
		<th>Nodes</th>
		<th>Comment</th>
	</tr>
<? foreach($moves as $move){ ?>
	<tr class='l'>
		<td><?= $move['movenum'] ?></td>
		<td><?= $move['position'] ?></td>
		<td><?= $move['side'] ?></td>
		<td><?= $move['value'] ?></td>
		<td><?= $move['outcome'] ?></td>
		<td><?= $move['timetaken'] ?></td>
		<td><?= $move['work'] ?></td>
		<td><?= $move['nodes'] ?></td>
		<td><?= $move['comment'] ?></td>
	</tr>
<? } ?>
</table>
<?

	return true;
}


function playername($id, $players){
	$names = array();
	while($id){
		$names[] = $players[$id]['name'];
		$id = $players[$id]['parent'];
	}
	return implode(" > ", array_reverse($names));
}
