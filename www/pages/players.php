<?

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

jQuery.fn.save = function() {
	return this.each(function() {
		$.data(this, 'savedvalue', $(this).html());
	});
};
jQuery.fn.revert = function() {
	return this.each(function() {
		$(this).html($.data(this, 'savedvalue'));
	});
};
jQuery.fn.editbox = function(options) {
	return this.each(function() {
		var value = $(this).html();
		var input = $('<input>').val(value);
		$.each(options, function(k,v){
			input.attr(k, v);
		});
		$(this).html(input);
	});
};
jQuery.fn.input_obj = function(ret) {
	ret = ret || { };
	this.find('input').each(function() {
		ret[this.name] = this.value;
	});
	return ret;
};


$('a.newhuman').live('click', function(e){
	e.preventDefault();

	var tr = $('<tr class="l"><td></td>' +
		'<td><input name="name"></td><td></td><td></td>' +
		'<td><a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a></td></tr>');
	tr.find('a.save').click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= $playertypes['person'] ?> });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				tr.replaceWith('<tr class="l"><td><input type="hidden" name="id" value="' + data.id + '"></td>' +
					'<td>' + data.name + '</td><td></td><td></td>' +
					'<td><a class="edithuman" href="#">Edit</a></td></tr>');
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
		var input = tr.input_obj({type: <?= $playertypes['person'] ?> });
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

	var tr = $('<tr class="l"><td></td>' +
		'<td><input name="name"></td>' +
		'<td><input name="params"></td>' +
		'<td><input name="weight" size="3"></td>' +
		'<td><a class="save" href="#">Save</a> ' +
		'<a class="cancel" href="#">Cancel</a></td></tr>');
	tr.find('a.save').click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= $playertypes['program'] ?> });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				tr.replaceWith('<tr class="l"><td><input type="hidden" name="id" value="' + data.id + '"></td>' +
					'<td>' + data.name + '</td><td></td><td></td>' +
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
	$(tds[3]).editbox({name: "weight", size:3});

	var links = $('<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>');
	links.filter("a.save").click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= $playertypes['program'] ?> });
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

	var tr = $('<tr class="l"><td></td>' +
		'<td class="spacer"><input name="name"></td>' +
		'<td><input name="params"></td>' +
		'<td><input name="weight" size="3"></td>' +
		'<td><a class="save" href="#">Save</a> ' +
		'<a class="cancel" href="#">Cancel</a></td></tr>');
	var parent = $(this).attr('parent');
	tr.find('a.save').click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= $playertypes['baseline'] ?>, 'parent': parent });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				tr.replaceWith('<tr class="l"><td><input type="hidden" name="id" value="' + data.id + '">' +
					'<input type="hidden" name="parent" value="' + data.parent + '"></td>' +
					'<td class="spacer">' + data.name + '</td><td>' + data.params + '</td><td>' + data.weight + '</td>' +
					'<td><a class="editbaseline" href="#">Edit</a></td></tr>'
					);
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
	$(tds[3]).editbox({name: "weight", size:3});

	var links = $('<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>');
	links.filter("a.save").click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= $playertypes['baseline'] ?> });
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

	var tr = $('<tr class="l"><td></td>' +
		'<td class="spacer"><input name="name"></td>' +
		'<td></td>' +
		'<td><input name="weight" size="3"></td>' +
		'<td><a class="save" href="#">Save</a> ' +
		'<a class="cancel" href="#">Cancel</a></td></tr>');
	var parent = $(this).attr('parent');
	tr.find('a.save').click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= $playertypes['testgroup'] ?>, 'parent': parent });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				tr.replaceWith('<tr class="l"><td><input type="hidden" name="id" value="' + data.id + '">' +
					'<input type="hidden" name="parent" value="' + data.parent + '"></td>' +
					'<td class="spacer">' + data.name + '</td><td></td><td>' + data.weight + '</td>' +
					'<td><a class="edittestgroup" href="#">Edit</a> ' +
					'<a class="newtestcase" href="#" parent="' + data.id + '">New Value</a></td></tr>'
					);
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
	$(tds[3]).editbox({name: "weight", size:3});

	var links = $('<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>');
	links.filter("a.save").click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= $playertypes['testgroup'] ?> });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				$(tds[1]).html(data.name)
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

	var tr = $('<tr class="l"><td></td>' +
		'<td class="spacer"><input name="name"></td>' +
		'<td><input name="params"></td>' +
		'<td><input name="weight" size="3"></td>' +
		'<td><a class="save" href="#">Save</a> ' +
		'<a class="cancel" href="#">Cancel</a></td></tr>');
	var parent = $(this).attr('parent');
	tr.find('a.save').click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= $playertypes['testcase'] ?>, 'parent': parent });
		$.post("/players/save", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				tr.replaceWith('<tr class="l"><td><input type="hidden" name="id" value="' + data.id + '">' +
					'<input type="hidden" name="parent" value="' + data.parent + '"></td>' +
					'<td class="spacer2">' + data.name + '</td><td>' + data.params + '</td><td>' + data.weight + '</td>' +
					'<td><a class="edittestcase" href="#">Edit</a></td></tr>'
					);
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
	$(tds[3]).editbox({name: "weight", size:3});

	var links = $('<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>');
	links.filter("a.save").click(function(e){
		e.preventDefault();
		var input = tr.input_obj({type: <?= $playertypes['testcase'] ?> });
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


	<table width="750">
	<tr>
		<th></td>
		<th>Name</td>
		<th>Param</td>
		<th>Weight</td>
		<th></td>
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
	global $db, $playertypes;

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

