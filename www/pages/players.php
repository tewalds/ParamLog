<?

function players_route($input, $user){
	switch($input['action']){
		case 'Set Weight': return players_set_weight($input);
		case 'Edit'      : return players_edit($input);
		default:           return players_list($input);
	}
}

function players_list($input, $user){
	global $db, $playertypes;
	
	$players = $db->pquery("SELECT * FROM players WHERE userid = ? ORDER BY name", $user->userid)->fetchrowset('id');

	$persons    = array(); // [ids of persons]
	$programs   = array(); // [ids of programs]
	$baselines  = array(); // {program => [ids of baselines]}
	$testgroups = array(); // {program => [ids of testgroups]}
	$testcases  = array(); // {testgroup => [ids of testcases]}

	foreach($players as $player){
		switch($player['type']){
			case $playertypes['person']:    $persons[] = $player['id']; break;
			case $playertypes['program']:   $programs[] = $player['id']; break;
			case $playertypes['baseline']:
				undefset($baselines[$player['parent']], array());
				$baselines[$player['parent']][] = $player['id'];
				break;
			case $playertypes['testgroup']:
				undefset($testgroups[$player['parent']], array());
				$testgroups[$player['parent']][] = $player['id'];
				break;
			case $playertypes['testcase']:
				undefset($testcases[$player['parent']], array());
				$testcases[$player['parent']][] = $player['id'];
				break;
			default:
				trigger_error("Unknown player type... " . print_r($player), E_USER_WARNING);
		}
	}

?>
<script>
$('a.newhuman').live('click', function(e){
	e.preventDefault();

	if($('#newhuman').length == 0){
		var n = $('<tr id="newhuman" class="l"><td></td>' +
			'<td colspan="3"><input></td><td></td><td></td>' +
			'<td><a class="save" href="#">Save</a> ' +
			'<a class="cancel" href="#">Cancel</a></td></tr>');
		var input = n.find('input');
		n.find('a.save').click(function(e){
			e.preventDefault();
			$.post("/players/savehuman", {name : input.val() }, function(data){
				if(data.error){
					alert(data.error);
				}else{
					$('#newhuman').replaceWith('<tr class="l"><td><input type="hidden" value="' + data.id + '"></td>' +
						'<td colspan="3">' + data.name + '</td><td></td><td></td>' +
						'<td><a class="edithuman" href="#">Edit</a></td></tr>');
				}
			}, 'json');
		});
		n.find('a.cancel').click(function(e){
			e.preventDefault();
			$('#newhuman').remove();
		});
		$('#humans').after(n);
	}
});

$('a.edithuman').live('click', function(e){
	e.preventDefault();
	var tr = $(this).parent().parent();
	var tds = tr.children();

	var td = $(tds[1]);
	var value = td.html();
	var input = $('<input name="name">').val(value);
	td.html(input);

	td = $(tds[4])
	var links = $('<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>');
	links.filter("a.save").click(function(e){
		e.preventDefault();

		var input = tr.find('input');
		$.post("/players/savehuman", input.serialize(), function(data){
			if(data.error){
				alert(data.error);
			}else{
				$(tds[1]).html(data.name)
				$(tds[4]).html('<a class="edithuman" href="#">Edit</a>');
			}
		}, 'json');
	});
	links.filter("a.cancel").click(function(e){
		e.preventDefault();

		$(tds[1]).html(value)
		$(tds[4]).html('<a class="edithuman" href="#">Edit</a>');
	});
	td.html(links);
});

</script>


	<form method="post" action="/players/updateweights">
	<table width=700>
	<tr>
		<th></td>
		<th colspan="3">Name</td>
		<th>Param</td>
		<th>Weight</td>
		<th></td>
	</tr>
	<tr class="l2" id="humans"><td></td>
		<td colspan="5"><b>Humans:</b></td>
		<td><a class="newhuman" href="#">New Human</a></td>
	</tr>
	<? foreach($persons as $pid){
		$player = $players[$pid]; ?>
		<tr class="l">
		<td><input type="hidden" name="id" value="<?= $pid ?>"></td>
		<td colspan="3"><?= $player['name'] ?></td>
		<td></td>
		<td></td>
		<td><a class="edithuman" href="#">Edit</a></td>
		</tr>
	<?	} ?>
	<tr class="l2" id="programs"><td></td>
		<td colspan="5"><b>Programs:</b></td>
		<td>New Program</td>
	</tr>
	<? foreach($programs as $pid){
		$player = $players[$pid]; ?>
		<tr class="l">
		<td><input type=checkbox name=check[] value="<?= $player['id'] ?>"></td>
		<td colspan="3"><?= $player['name'] ?></td>
		<td><?= $player['params'] ?></td>
		<td><?= $player['weight'] ?></td>
		<td>Edit</td>
		</tr>
		<tr class="l2" id="baseline<?= $pid ?>"><td></td>
			<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<td colspan="4"><b>Baselines:</b></td>
			<td>New Baseline</td>
		</tr>
		<? foreach($baselines[$pid] as $bid){
			$player = $players[$bid]; ?>
			<tr class="l">
			<td><input type=checkbox name=check[] value="<?= $player['id'] ?>"></td>
			<td></td>
			<td colspan="2"><?= $player['name'] ?></td>
			<td><?= $player['params'] ?></td>
			<td><?= $player['weight'] ?></td>
			<td>Edit</td>
			</tr>
		<? } ?>
		<tr class="l2" id="testgroups<?= $pid ?>"><td></td>
			<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
			<td colspan="4"><b>Test Groups:</b></td>
			<td>New Test Group</td>
		</tr>
		<? foreach($testgroups[$pid] as $gid){
			$player = $players[$gid]; ?>
			<tr class="l">
			<td><input type=checkbox name=check[] value="<?= $player['id'] ?>"></td>
			<td></td>
			<td colspan="2"><?= $player['name'] ?></td>
			<td><?= $player['params'] ?></td>
			<td><?= $player['weight'] ?></td>
			<td>Edit, New Value</td>
			</tr>
			<? foreach($testcases[$gid] as $tid){
				$player = $players[$tid]; ?>
				<tr class="l">
				<td><input type=checkbox name=check[] value="<?= $player['id'] ?>"></td>
				<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td><?= $player['name'] ?></td>
				<td><?= $player['params'] ?></td>
				<td><?= $player['weight'] ?></td>
				<td>Edit</td>
				</tr>
			<? } ?>
		<? } ?>
	<? } ?>
	<tr class="f">
		<td colspan="7">
			Weight: <input type="text" name="weight" value="0" size="3">
			<input type="submit" name="action" value="Set Weight">
		</td>
	</tr>
	</table>
	</form>

<?
	return true;
}


function players_set_weight($input, $user){
	global $db;

	$db->pquery("UPDATE players SET weight = ? WHERE userid = ? && id IN (?)", $input['weight'], $user->userid, $input['check']);
	
	echo "Players updated<br>";
	
	return players_list($input, $user);
}

function players_edit($input, $user){
	global $db;

	$players = $db->pquery("SELECT * FROM players WHERE player IN (?) ORDER BY name", $input['check'])->fetchrowset();

?>
	<form action="/players/update" method="post">
	<table>
	<tr><th colspan=4>Edit players</th></tr>
	<tr>
		<th>ID</th>
		<th>Name</th>
		<th>Weight</th>
		<th>Param</th>
	</tr>
<?
	$i = 1;
	foreach($players as $player){
?>		<tr class="l<?= ($i = 3 - $i) ?>">
		<td><input type=hidden name="players[]" value="<?= $player['player'] ?>"/><?= $player['player'] ?></td>
		<td><input type="text" name="names[]"   value="<?= htmlentities($player['name'])   ?>" size=40 /></td>
		<td><input type="text" name="weights[]" value="<?= htmlentities($player['weight']) ?>" size=5  /></td>
		<td><input type="text" name="params[]"  value="<?= htmlentities($player['params']) ?>" size=30 /></td>
		</tr>
<?	} ?>
	<tr class='f'><td colspan=4>
		<input type=submit name=action value=Update>
	</td></tr>
	</table>
	</form>
<?
	return true;
}

function players_save_human($input, $user){
	global $db, $playertypes;

	if($input['name']){
		if($input['id']){
			$res = $db->pquery("UPDATE players SET name = ? WHERE userid = ? && id = ? && type = ?", $input['name'], $user->userid, $input['id'], $playertypes['person']);
			if($res->affectedrows() == 0)
				echo json(array("error" => "no row to update"));
			else
				echo json(array("id" => $input['id'], "name" => htmlentities($input['name'])));
		}else{
			$res = $db->pquery("INSERT INTO players SET userid = ?, type = ?, name = ?", $user->userid, $playertypes['person'], $input['name']);
			echo json(array("id" => $res->insertid(), "name" => htmlentities($input['name'])));
		}
	}else{
		echo json(array("error" => "empty name"));
	}
	return false;
}

function players_update($input, $user){
	global $db;

	for($i = 0; $i < count($input['players']); $i++)
		$db->pquery("UPDATE players SET name = ?, weight = ?, params = ? WHERE player = ?", 
			$input['names'][$i], $input['weights'][$i], $input['params'][$i], $input['players'][$i]);

	echo "Players Updated<br>\n";
	
	return players_list(array(), $user);
}

function players_add($input, $user){
	global $db;

	for($i = 0; $i < count($input['names']); $i++){
		if(empty($input['names'][$i]) || !isset($input['weights'][$i]) || empty($input['params'][$i]))
			continue;
	
		if($db->pquery("INSERT INTO players SET name = ?, weight = ?, params = ?",
			$input['names'][$i], $input['weights'][$i], $input['params'][$i])->affectedrows() == 1)
			echo $input['names'][$i] . " input successfully<br>\n";
		else
			echo $input['names'][$i] . " failed<br>\n";
	}
	return players_list(array(), $user);
}

