<?

function getwork($data, $user){
	global $db;

	$u = $user->userid;

	$row = $db->pquery(
	//round robin of baselines
		"(SELECT
			times.id      as timeid,
			times.move    as timemove,
			times.game    as timegame,
			times.sims    as timesims,
			sizes.id      as sizeid,
			sizes.size    as sizeparam,
			p1base.id     as p1id,
			p1prog.params as p1cmd,
			p1base.params as p1config,
			p2base.id     as p2id,
			p2prog.params as p2cmd,
			p2base.params as p2config,
			''            as p2test,
			IF(results.numgames IS NULL, 0, results.numgames / (
				p1prog.weight *
				p1base.weight *
				p2prog.weight *
				p2base.weight *
				sizes.weight *
				times.weight *
				results.weight )) AS priority
		FROM
			players      AS p1prog
			JOIN players AS p1base
			JOIN players AS p2prog
			JOIN players AS p2base
			JOIN sizes
			JOIN times
			LEFT JOIN results ON
				results.userid = ? AND
				results.player1 = p1base.id AND
				results.player2 = p2base.id AND
				results.size = sizes.id AND
				results.time = times.id
		WHERE
			p1prog.userid = ? AND
			p2prog.userid = ? AND
			sizes.userid = ? AND
			times.userid = ? AND

			p1prog.type = " . P_PROGRAM . " AND
			p1base.type = " . P_BASELINE . " AND
			p1prog.id = p1base.parent AND

			p2prog.type = " . P_PROGRAM . " AND
			p2base.type = " . P_BASELINE . " AND
			p2prog.id = p2base.parent AND

			p1base.id < p2base.id AND

			p1prog.weight > 0 AND
			p1base.weight > 0 AND
			p2prog.weight > 0 AND
			p2base.weight > 0 AND
			sizes.weight > 0 AND
			times.weight > 0 AND
			(results.weight IS NULL OR results.weight > 0)
		ORDER BY priority ASC, RAND()
		LIMIT 1)
	UNION ".
	//baselines against test specializations of the same program
		"(SELECT
			times.id      as timeid,
			times.move    as timemove,
			times.game    as timegame,
			times.sims    as timesims,
			sizes.id      as sizeid,
			sizes.size    as sizeparam,
			p1base.id     as p1id,
			p1prog.params as p1cmd,
			p1base.params as p1config,
			p2test.id     as p2id,
			p2prog.params as p2cmd,
			p1base.params as p2config,
			CONCAT(p2group.params, p2test.params) as p2test,
			IF(results.numgames IS NULL, 0, results.numgames / (
				p1prog.weight *
				p1base.weight *
				p2prog.weight *
				p2group.weight *
				p2test.weight *
				sizes.weight *
				times.weight *
				results.weight )) as priority
		FROM
			players      AS p1prog
			JOIN players AS p1base
			JOIN players AS p2prog
			JOIN players AS p2group
			JOIN players AS p2test
			JOIN sizes
			JOIN times
			LEFT JOIN results ON
				results.userid = ? AND
				results.player1 = p1base.id AND
				results.player2 = p2test.id AND
				results.size = sizes.id AND
				results.time = times.id
		WHERE
			p1prog.userid = ? AND
			p2prog.userid = ? AND
			sizes.userid = ? AND
			times.userid = ? AND

			p1prog.type = " . P_PROGRAM . " AND
			p1base.type = " . P_BASELINE . " AND
			p1prog.id = p1base.parent AND

			p2prog.type = " . P_PROGRAM . " AND
			p2group.type = " . P_TESTGROUP . " AND
			p2test.type = " . P_TESTCASE . " AND
			p2prog.id = p2group.parent AND
			p2group.id = p2test.parent AND

			p1prog.id = p2prog.id AND

			p1prog.weight > 0 AND
			p1base.weight > 0 AND
			p2prog.weight > 0 AND
			p2group.weight > 0 AND
			p2test.weight > 0 AND
			sizes.weight > 0 AND
			times.weight > 0 AND
			(results.weight IS NULL OR results.weight > 0)
		ORDER BY priority ASC, RAND()
		LIMIT 1)
		ORDER BY priority ASC, RAND()",
			$u, $u, $u, $u, $u, $u, $u, $u, $u, $u)->fetchrow();

	if(!$row)
		return echo_json(array('error' => "No work"));

	settype($row['timeid'],   'int');
	settype($row['timemove'], 'float');
	settype($row['timegame'], 'float');
	settype($row['timesims'], 'int');
	settype($row['sizeid'],   'int');
	settype($row['p1id'],     'int');
	settype($row['p2id'],     'int');

	return echo_json($row);
}

function lookup_game_id($data, $user){
	global $db;

	if($data['lookup']){
		$row = $db->pquery("SELECT id FROM games WHERE userid = ? && lookup = ?", $user->userid, $data['lookup'])->fetchrow();
		if($row)
			echo $row['id'];
		else
			echo "0";
	}else
		echo "0";

	return false;
}

function save_game($data, $user){
	global $db;

	$outcome = ($data['outcomeref'] ? $data['outcomeref'] : ($data['outcome1'] == $data['outcome2'] ? $data['outcome1'] : 0));

	if($data['id']){
		$affected = $db->pquery("UPDATE games SET outcome1 = ?, outcome2 = ?, outcomeref = ? WHERE userid = ? && id = ? && saved = 0",
			$data['outcome1'], $data['outcome2'], $data['outcomeref'], $user->userid, $data['id'])->affectedrows();

		if(!$affected)
			$data['saveresult'] = 0;
	}else{
		if(!$data['player1'] || !$data['player2'] || !$data['size'] || !$data['time'])
			return json_error("Missing params");

		if(!$data['host'])
			$data['host'] = gethostbyaddr($_SERVER["REMOTE_ADDR"]);

		if($data['jsonmoves'])
			$moves = json_decode($data['jsonmoves'], true);
		else
			$moves = array();

		if($data['timestamp'] == 0)
			$data['timestamp'] = time();

		$data['id'] = $db->pquery("INSERT INTO games SET userid = ?, lookup = ?, player1 = ?, player2 = ?, size = ?, time = ?,
			timestamp = ?, nummoves = ?, outcome1 = ?, outcome2 = ?, outcomeref = ?, version1 = ?, version2 = ?, host = ?, saved = ?",
			$user->userid, $data['lookup'], $data['player1'], $data['player2'], $data['size'], $data['time'], time(), count($moves),
			$data['outcome1'], $data['outcome2'], $data['outcomeref'], $data['version1'], $data['version2'], $data['host'], (int)($data['saveresult'] && $outcome > 0))->insertid();

		//insert all the moves in a single query
		$parts = array();
		foreach($moves as $move)
			$parts[] = $db->prepare("(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
				$user->userid, $data['id'], $move['movenum'], $move['position'], $move['side'], $move['value'], $move['outcome'], $move['timetaken'], $move['work'], $move['nodes'], $move['comment']);

		if(count($parts))
			$db->query("INSERT INTO moves (userid, gameid, movenum, position, side, value, outcome, timetaken, work, nodes, comment) VALUES " . implode(", ", $parts));
	}
	$row = $db->pquery("SELECT * FROM games WHERE userid = ? && id = ?", $user->userid, $data['id'])->fetchrow();

	if($outcome > 0 && $data['saveresult']){
		$db->pquery("INSERT INTO results SET userid = ?, player1 = ?, player2 = ?, size = ?, time = ?, weight = 1, p1wins = ?, p2wins = ?, ties = ?, numgames = 1
			ON DUPLICATE KEY UPDATE p1wins = p1wins + ?, p2wins = p2wins + ?, ties = ties + ?, numgames = numgames + 1",
			$user->userid, $row['player1'], $row['player2'], $row['size'], $row['time'],
			(int)($outcome == 1), (int)($outcome == 2), (int)($outcome == 3),
			(int)($outcome == 1), (int)($outcome == 2), (int)($outcome == 3));
	}

	echo json(h($row));
	return false;
}

function add_move($data, $user){
	global $db;

	if(!$data['gameid'])
		return json_error('Missing gameid');

	$game = $db->pquery("SELECT * FROM games WHERE userid = ? && id = ?", $user->userid, $data['gameid'])->fetchrow();
	if(!$game)
		return json_error('Invalid gameid');

	if(!$data['movenum'])
		$data['movenum'] = $game['nummoves']+1;

	$id = $db->pquery("INSERT INTO moves SET userid = ?, gameid = ?, movenum = ?, position = ?, side = ?,
		value = ?, outcome = ?, timetaken = ?, work = ?, nodes = ?, comment = ?", $user->userid, $data['gameid'], $data['movenum'],
		$data['position'], $data['side'], $data['value'], $data['outcome'], $data['timetaken'], $data['work'], $data['nodes'], $data['comment'])->insertid();

	$db->pquery("UPDATE games SET nummoves = nummoves + 1 WHERE userid = ? && id = ?", $user->userid, $data['gameid']);

	return false;
}

function add_moves($data, $user){
	global $db;

	if(!$data['gameid'])
		return json_error('Missing gameid');

	$game = $db->pquery("SELECT * FROM games WHERE userid = ? && id = ?", $user->userid, $data['gameid'])->fetchrow();
	if(!$game)
		return json_error('Invalid gameid');

	$moves = json_decode($data['jsonmoves'], true);

	foreach($moves as $move)
		$db->pquery("INSERT INTO moves SET userid = ?, gameid = ?, movenum = ?, position = ?, side = ?,	value = ?, outcome = ?, timetaken = ?, work = ?, nodes = ?, comment = ?",
			$user->userid, $data['gameid'], $move['movenum'], $move['position'], $move['side'], $move['value'], $move['outcome'], $move['timetaken'], $move['work'], $move['nodes'], $move['comment']);

	$db->pquery("UPDATE games SET nummoves = nummoves + ? WHERE userid = ? && id = ?", count($moves), $user->userid, $data['gameid']);

	return false;
}


/*
 * outcome=1 -> player 1 wins, outcome=2 -> player 2 wins, outcome=3 -> tie game
 */
function save_result($data, $user){
	global $db;

	if($data['outcome'] < 1 || $data['outcome'] > 3)
		return json_error("Invalid outcome: $data[outcome]");

	$rows = $db->pquery("SELECT id, type FROM players WHERE userid = ? && id in ?", $user->userid, array($data['player1'], $data['player2']))->fetchfieldset();
	if(count($rows) != 2) return json_error("Invalid players");

	if(!(($rows[$data['player1']] == P_BASELINE && $rows[$data['player2']] == P_TESTCASE) || $data['player1'] < $data['player2'])){
		swap($data['player1'], $data['player2']);
		if($data['outcome'] == 1 || $data['outcome'] == 2)
			$data['outcome'] = 3 - $data['outcome'];
	}

	$numrows = $db->pquery("SELECT id FROM sizes WHERE userid = ? && id = ?", $user->userid, $data['size'])->numrows();
	if($numrows != 1) return json_error("Invalid size");

	$numrows = $db->pquery("SELECT id FROM times WHERE userid = ? && id = ?", $user->userid, $data['time'])->numrows();
	if($numrows != 1) return json_error("Invalid time");

	$db->pquery("INSERT INTO results SET userid = ?, player1 = ?, player2 = ?, size = ?, time = ?, weight = 1, p1wins = ?, p2wins = ?, ties = ?, numgames = 1
		ON DUPLICATE KEY UPDATE p1wins = p1wins + ?, p2wins = p2wins + ?, ties = ties + ?, numgames = numgames + 1",
		$user->userid, $data['player1'], $data['player2'], $data['size'], $data['time'],
		(int)($data['outcome'] == 1), (int)($data['outcome'] == 2), (int)($data['outcome'] == 3),
		(int)($data['outcome'] == 1), (int)($data['outcome'] == 2), (int)($data['outcome'] == 3));

	echo "1";
	return false;
}

