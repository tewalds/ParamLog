<?

function players_list($input, $user){
	global $db;

	$players = $db->pquery("SELECT * FROM players WHERE userid = ?", $user->userid)->fetchrowset('id');
	uasort($players, 'cmpname');

	$persons    = array(); // [ids of persons]
	$programs   = array(); // [ids of programs]
	$baselines  = array(); // {program => [ids of baselines]}
	$testgroups = array(); // {program => [ids of testgroups]}
	$testcases  = array(); // {testgroup => [ids of testcases]}

	foreach($players as $player){
		switch($player['type']){
			case P_PERSON:    $persons[] = $player['id']; break;
			case P_PROGRAM:
				$programs[] = $player['id'];
				undefset($baselines[$player['id']], array());
				undefset($testgroups[$player['id']], array());
				break;
			case P_BASELINE:
				undefset($baselines[$player['parent']], array());
				$baselines[$player['parent']][] = $player['id'];
				break;
			case P_TESTGROUP:
				undefset($testgroups[$player['parent']], array());
				$testgroups[$player['parent']][] = $player['id'];
				undefset($testcases[$player['id']], array());
				break;
			case P_TESTCASE:
				undefset($testcases[$player['parent']], array());
				$testcases[$player['parent']][] = $player['id'];
				break;
			default:
				trigger_error("Unknown player type... " . print_r($player), E_USER_WARNING);
		}
	}

?>
<script>

function buildrow(data){
	var str = '';
	str += '<tr class="l">';
	str += '<td>';
	if(data.id)     str += '<input type="hidden" name="id" value="' + data.id + '">';
	if(data.parent) str += '<input type="hidden" name="parent" value="' + data.parent + '">';
	str += '</td>';
	str += '<td class="' + (data.nameclass || '') + '">' + (data.name || '') + '</td>';
	str += '<td>' + (data.params || '') + '</td>';
	str += '<td>' + (data.weight || '') + '</td>';
	str += '<td>' + (data.links || '') + '</td>';
	str += '</tr>';

	return $(str);
}

$('a.newhuman').live('click', function(e){
	e.preventDefault();

	var tr = buildrow( { links : '<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>' });
	var tds = tr.children();
	$(tds[1]).editbox({name: "name"});

	tr.find('a.save').click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= P_PERSON ?> });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				data.links = '<a class="edithuman" href="#">Edit</a>';
				tr.replaceWith(buildrow(data));
			}
		}, 'json');
	});
	tr.find('a.cancel').click(function(e){
		e.preventDefault();
		tr.remove();
	});
	$('#humans').after(tr);
});

$('a.edithuman').live('click', function(e){
	e.preventDefault();
	var tr = $(this).parent().parent();
	var tds = tr.children();

	tds.save();

	$(tds[1]).editbox({name: "name"});

	var links = $('<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>');
	links.filter("a.save").click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= P_PERSON ?> });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				$(tds[1]).html(data.name)
				$(tds[4]).revert();
			}
		}, 'json');
	});
	links.filter("a.cancel").click(function(e){
		e.preventDefault();
		tds.revert();
	});
	$(tds[4]).html(links);
});


$('a.newprogram').live('click', function(e){
	e.preventDefault();

	var tr = buildrow( { links : '<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>' });
	var tds = tr.children();
	$(tds[1]).editbox({name: "name"});
	$(tds[2]).editbox({name: "params"});
	$(tds[3]).editbox({name: "weight", value: 1, width: 80});

	tr.find('a.save').click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= P_PROGRAM ?> });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				tr.replaceWith('<tr class="l"><td><input type="hidden" name="id" value="' + data.id + '"></td>' +
					'<td>' + data.name + '</td><td>' + data.params + '</td><td>' + data.weight + '</td>' +
					'<td><a class="editprogram" href="#">Edit</a></td></tr>' +
					'<tr class="l2"><td></td><td class="spacer" colspan="3"><b>Baselines:</b></td>' +
					'<td><a class="newbaseline" href="#" parent="' + data.id + '">New Baseline</a></td></tr>' +
					'<tr class="l2"><td></td><td class="spacer" colspan="3"><b>Test Groups:</b></td>' +
					'<td><a class="newtestgroup" href="#" parent="' + data.id + '">New Test Group</a></td></tr>'
					);
			}
		}, 'json');
	});
	tr.find('a.cancel').click(function(e){
		e.preventDefault();
		tr.remove();
	});
	$('#programs').after(tr);
});

$('a.editprogram').live('click', function(e){
	e.preventDefault();
	var tr = $(this).parent().parent();
	var tds = tr.children();

	tds.save();

	$(tds[1]).editbox({name: "name"});
	$(tds[2]).editbox({name: "params"});
	$(tds[3]).editbox({name: "weight"});

	var links = $('<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>');
	links.filter("a.save").click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= P_PROGRAM ?> });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				$(tds[1]).html(data.name)
				$(tds[2]).html(data.params)
				$(tds[3]).html(data.weight)
				$(tds[4]).revert();
			}
		}, 'json');
	});
	links.filter("a.cancel").click(function(e){
		e.preventDefault();
		tds.revert();
	});
	$(tds[4]).html(links);
});

$('a.newbaseline').live('click', function(e){
	e.preventDefault();

	var tr = buildrow( { links : '<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>' });
	var tds = tr.children();
	$(tds[1]).editbox({name: "name"});
	$(tds[2]).editbox({name: "params"});
	$(tds[3]).editbox({name: "weight", value: 1, width: 80});

	var parent = $(this).attr('parent');
	tr.find('a.save').click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= P_BASELINE ?>, 'parent': parent });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				tr.replaceWith(buildrow($.extend(data, { nameclass: 'spacer', links : '<a class="editbaseline" href="#">Edit</a>' })));
			}
		}, 'json');
	});
	tr.find('a.cancel').click(function(e){
		e.preventDefault();
		tr.remove();
	});
	$(this).parent().parent().after(tr);
});


$('a.editbaseline').live('click', function(e){
	e.preventDefault();
	var tr = $(this).parent().parent();
	var tds = tr.children();

	tds.save();

	$(tds[1]).editbox({name: "name"});
	$(tds[2]).editbox({name: "params"});
	$(tds[3]).editbox({name: "weight"});

	var links = $('<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>');
	links.filter("a.save").click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= P_BASELINE ?> });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				$(tds[1]).html(data.name)
				$(tds[2]).html(data.params)
				$(tds[3]).html(data.weight)
				$(tds[4]).revert();
			}
		}, 'json');
	});
	links.filter("a.cancel").click(function(e){
		e.preventDefault();
		tds.revert();
	});
	$(tds[4]).html(links);
});

$('a.newtestgroup').live('click', function(e){
	e.preventDefault();

	var tr = buildrow( { nameclass : 'spacer', links : '<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>' });
	var tds = tr.children();
	$(tds[1]).editbox({name: "name"});
	$(tds[2]).editbox({name: "params"});
	$(tds[3]).editbox({name: "weight", value: 1, width: 80});

	var parent = $(this).attr('parent');
	tr.find('a.save').click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= P_TESTGROUP ?>, 'parent': parent });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				tr.replaceWith(buildrow($.extend(data, { nameclass: 'spacer', links : '<a class="newtestcase" href="#" parent="' + data.id + '">New Value</a>' })));
			}
		}, 'json');
	});
	tr.find('a.cancel').click(function(e){
		e.preventDefault();
		tr.remove();
	});
	$(this).parent().parent().after(tr);
});

$('a.edittestgroup').live('click', function(e){
	e.preventDefault();
	var tr = $(this).parent().parent();
	var tds = tr.children();

	tds.save();

	$(tds[1]).editbox({name: "name"});
	$(tds[2]).editbox({name: "params"});
	$(tds[3]).editbox({name: "weight"});

	var links = $('<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>');
	links.filter("a.save").click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= P_TESTGROUP ?> });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				$(tds[1]).html(data.name)
				$(tds[2]).html(data.params)
				$(tds[3]).html(data.weight)
				$(tds[4]).revert();
			}
		}, 'json');
	});
	links.filter("a.cancel").click(function(e){
		e.preventDefault();
		tds.revert();
	});
	$(tds[4]).html(links);
});

$('a.newtestcase').live('click', function(e){
	e.preventDefault();

	var tr = buildrow( { nameclass: 'spacer2', links : '<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>' });
	var tds = tr.children();
	$(tds[1]).editbox({name: "name"});
	$(tds[2]).editbox({name: "params"});
	$(tds[3]).editbox({name: "weight", value: 1, width: 80});

	var parent = $(this).attr('parent');
	tr.find('a.save').click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= P_TESTCASE ?>, 'parent': parent });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				tr.replaceWith(buildrow($.extend(data, { nameclass: 'spacer2', links : '<a class="edittestcase" href="#">Edit</a>' })));
			}
		}, 'json');
	});
	tr.find('a.cancel').click(function(e){
		e.preventDefault();
		tr.remove();
	});
	$(this).parent().parent().after(tr);
});

$('a.edittestcase').live('click', function(e){
	e.preventDefault();
	var tr = $(this).parent().parent();
	var tds = tr.children();

	tds.save();

	$(tds[1]).editbox({name: "name"});
	$(tds[2]).editbox({name: "params"});
	$(tds[3]).editbox({name: "weight"});

	var links = $('<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>');
	links.filter("a.save").click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= P_TESTCASE ?> });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				$(tds[1]).html(data.name)
				$(tds[2]).html(data.params)
				$(tds[3]).html(data.weight)
				$(tds[4]).revert();
			}
		}, 'json');
	});
	links.filter("a.cancel").click(function(e){
		e.preventDefault();
		tds.revert();
	});
	$(tds[4]).html(links);
});


</script>

	<table width="100%">
	<tr>
		<th></td>
		<th>Name</td>
		<th>Param</td>
		<th width="80px">Weight</td>
		<th width="140px"></td>
	</tr>
	<tr class="l2" id="humans"><td></td>
		<td colspan="3"><b>Humans:</b></td>
		<td><a class="newhuman" href="#">New Human</a></td>
	</tr>
	<? foreach($persons as $pid){
		$player = $players[$pid]; ?>
		<tr class="l">
		<td><input type="hidden" name="id" value="<?= $pid ?>"></td>
		<td><?= $player['name'] ?></td>
		<td></td>
		<td></td>
		<td><a class="edithuman" href="#">Edit</a></td>
		</tr>
	<?	} ?>
	<tr class="l2" id="programs"><td></td>
		<td colspan="3"><b>Programs:</b></td>
		<td><a class="newprogram" href="#">New Program</a></td>
	</tr>
	<? foreach($programs as $pid){
		$player = $players[$pid]; ?>
		<tr class="l">
		<td>
			<input type="hidden" name="id" value="<?= $player['id'] ?>">
			<input type="hidden" name="parent" value="<?= $player['parent'] ?>">
		</td>
		<td><?= $player['name'] ?></td>
		<td><?= $player['params'] ?></td>
		<td><?= $player['weight'] ?></td>
		<td><a class="editprogram" href="#">Edit</a></td>
		</tr>
		<tr class="l2" id="baseline<?= $pid ?>"><td></td>
			<td class="spacer" colspan="3"><b>Baselines:</b></td>
			<td><a class="newbaseline" href="#" parent="<?= $pid ?>">New Baseline</a></td>
		</tr>
		<? foreach($baselines[$pid] as $bid){
			$player = $players[$bid]; ?>
			<tr class="l">
			<td>
				<input type="hidden" name="id" value="<?= $player['id'] ?>">
				<input type="hidden" name="parent" value="<?= $player['parent'] ?>">
			</td>
			<td class="spacer"><?= $player['name'] ?></td>
			<td><?= $player['params'] ?></td>
			<td><?= $player['weight'] ?></td>
			<td><a class="editbaseline" href="#">Edit</a></td>
			</tr>
		<? } ?>
		<tr class="l2" id="testgroups<?= $pid ?>"><td></td>
			<td class="spacer" colspan="3"><b>Test Groups:</b></td>
			<td><a class="newtestgroup" href="#" parent="<?= $pid ?>">New Test Group</a></td>
		</tr>
		<? foreach($testgroups[$pid] as $gid){
			$player = $players[$gid]; ?>
			<tr class="l">
			<td>
				<input type="hidden" name="id" value="<?= $player['id'] ?>">
				<input type="hidden" name="parent" value="<?= $player['parent'] ?>">
			</td>
			<td class="spacer"><?= $player['name'] ?></td>
			<td><?= $player['params'] ?></td>
			<td><?= $player['weight'] ?></td>
			<td><a class="edittestgroup" href="#">Edit</a> <a class="newtestcase" href="#" parent="<?= $gid ?>">New Value</a></td>
			</tr>
			<? foreach($testcases[$gid] as $tid){
				$player = $players[$tid]; ?>
				<tr class="l">
				<td>
					<input type="hidden" name="id" value="<?= $player['id'] ?>">
					<input type="hidden" name="parent" value="<?= $player['parent'] ?>">
				</td>
				<td class="spacer2"><?= $player['name'] ?></td>
				<td><?= $player['params'] ?></td>
				<td><?= $player['weight'] ?></td>
				<td><a class="edittestcase" href="#">Edit</a></td>
				</tr>
			<? } ?>
		<? } ?>
	<? } ?>
	</table>

<?
	return true;
}

function players_save($input, $user){
	global $db;

	if($input['name']){
		if($input['id']){
			$res = $db->pquery("UPDATE players SET name = ?, params = ?, weight = ? WHERE userid = ? && id = ?", $input['name'], $input['params'], $input['weight'], $user->userid, $input['id']);
		}else{
			$res = $db->pquery("INSERT INTO players SET userid = ?, type = ?, parent = ?, name = ?, params = ?, weight = ?", $user->userid, $input['type'], $input['parent'], $input['name'], $input['params'], $input['weight']);
			$input['id'] = $res->insertid();
		}
		echo json(h($input));
	}else{
		echo json(array("error" => "empty name"));
	}
	return false;
}

