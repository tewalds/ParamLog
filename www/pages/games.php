<?

function getgames($input, $user){
	global $db;

	$players = $db->pquery("SELECT id, type, parent, name FROM players WHERE userid = ?", $user->userid)->fetchrowset('id');

	$testcases = array();
	foreach($players as $p){
		if($p['type'] == P_TESTCASE){
			undefset($testcases[$p['parent']], array());
			$testcases[$p['parent']][] = $p['id'];
		}
	}

	foreach($input['players'] as $k => $p){
		if($players[$p]['type'] == P_TESTGROUP){
			unset($input['players'][$k]);
			foreach($testcases[$p] as $t)
				$input['players'][] = $t;
		}
	}


	if(empty($input['players']) && empty($input['baselines']) && empty($input['sizes']) && empty($input['times'])){
		$data = $db->pquery("SELECT * FROM games WHERE userid = ? && timestamp > UNIX_TIMESTAMP()-3600 ORDER BY id", $user->userid)->fetchrowset();
	}else if(empty($input['players']) || empty($input['baselines']) || empty($input['sizes']) || empty($input['times'])){
		echo "You must select options from all categories to see any results!";
		return true;
	}else{
		$ids = array_merge($input['players'], $input['baselines']);
		$data = $db->pquery("SELECT * FROM games WHERE userid = ? && player1 IN ? && player2 IN ? && time IN ? && size IN ? ORDER BY id LIMIT 100",
				$user->userid, $ids, $ids, $input['times'], $input['sizes'])->fetchrowset();
	}

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

	if(!$game){
		echo "Bad Game ID";
		return true;
	}

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
	<tr class='l'><th>Time of Game</th><td><?= date("F j, Y, g:i a", $game['timestamp']) ?></td></tr>
	<tr class='l'><th>Host</th><td><?= $game['host'] ?></td></tr>
	<tr class='l'><th>Saved Result</th><td><?= ($game['saved'] ? "Yes" : "No") ?></td></tr>
	<tr class='l'><th>Lookup name</th><td><?= $game['lookup'] ?></td></tr>
	<tr class='l'><th>Move string (<a href="/games/sgf?id=<?= $game['id'] ?>">sgf</a>)</th><td><? foreach($moves as $move) echo $move['position'] . " "	?></td></tr>
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

function gensgf($input, $user){
	global $db;

	$game = $db->pquery("SELECT * FROM games WHERE userid = ? && id = ?", $user->userid, $input['id'])->fetchrow();
	$moves = $db->pquery("SELECT * FROM moves WHERE userid = ? && gameid = ? ORDER BY movenum", $user->userid, $input['id'])->fetchrowset();

//	$players = $db->pquery("SELECT id, name, parent FROM players WHERE userid = ?", $user->userid)->fetchrowset('id');
	$sizes = $db->pquery("SELECT id, size FROM sizes WHERE userid = ?", $user->userid)->fetchfieldset();

	header("Content-disposition: attachment; filename=\"$game[id].sgf\"");

	echo "(;FF[4]SZ[" . $sizes[$game['size']] . "]";
	echo "PW[$game[player1]:$game[version1]]PB[$game[player2]:$game[version2]]";
	foreach($moves as $i => $move)
		echo ";" . ($i % 2 == 0 ? 'W' : 'B') . "[$move[position]]";
	echo ")";

	return false;
}

function deletegame($input, $user){
	global $db;

	$row = $db->pquery("SELECT * FROM games WHERE userid = ? && id = ?", $user->userid, $input['id'])->fetchrow();

	if($row){
		if($row['saved']){
			$outcome = ($row['outcomeref'] ? $row['outcomeref'] : ($row['outcome1'] == $row['outcome2'] ? $row['outcome1'] : 0));
			$db->pquery("UPDATE results SET p1wins = p1wins - ?, p2wins = p2wins - ?, ties = ties - ?, numgames = numgames - 1 WHERE
				userid = ? && player1 = ? && player2 = ? && size = ? && time = ?",
				(int)($outcome == 1), (int)($outcome == 2), (int)($outcome == 3), $row['userid'], $row['player1'], $row['player2'], $row['size'], $row['time']);
		}

		$db->pquery("DELETE FROM games WHERE id = ?", $row['id']);
		$db->pquery("DELETE FROM moves WHERE gameid = ?", $row['id']);
	}

	redirect("/games/show?id=$input[id]");

	return false;
}

function playername($id, $players){
	$names = array();
	while($id){
		$names[] = $players[$id]['name'];
		$id = $players[$id]['parent'];
	}
	return implode(" > ", array_reverse($names));
}
