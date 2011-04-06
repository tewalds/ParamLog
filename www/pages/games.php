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
			<td><?= $players[$players[$row['player1']]['parent']]['name'] . " " . $players[$row['player1']]['name'] ?></td>
			<td><?= $players[$players[$row['player2']]['parent']]['name'] . " " . $players[$row['player2']]['name'] ?></td>
			<td><?= $sizes[$row['size']] ?></td>
			<td><?= $times[$row['time']] ?></td>
			<td><?= $row['nummoves'] ?></td>
			<td><?= $outcomes[($row['outcomeref'] ? $row['outcomeref'] : ($row['outcome1'] == $row['outcome2'] ? $row['outcome1'] : 0))] ?></td>
			<td><?= $row['host']?></td>
		</tr>
<?	} ?>
	</table>

<?
	return true;
}


