<?

function timesize_list($input, $user){
	global $db;

	$times = $db->pquery("SELECT * FROM times WHERE userid = ? ORDER BY name", $user->userid)->fetchrowset('id');
	$sizes = $db->pquery("SELECT * FROM sizes WHERE userid = ? ORDER BY name", $user->userid)->fetchrowset('id');

?>
<script>

function buildtime(data){
	var str = '';
	str += '<tr class="l">';
	str += '<td>';
	if(data.id)     str += '<input type="hidden" name="id" value="' + data.id + '">';
	str += '</td>';
	str += '<td class="' + (data.nameclass || '') + '">' + (data.name || '') + '</td>';
	str += '<td>' + (data.move || '0') + '</td>';
	str += '<td>' + (data.game || '0') + '</td>';
	str += '<td>' + (data.sims || '0') + '</td>';
	str += '<td>' + (data.weight || '0') + '</td>';
	str += '<td>' + (data.links || '') + '</td>';
	str += '</tr>';

	return $(str);
}

function buildsize(data){
	var str = '';
	str += '<tr class="l">';
	str += '<td>';
	if(data.id)     str += '<input type="hidden" name="id" value="' + data.id + '">';
	str += '</td>';
	str += '<td class="' + (data.nameclass || '') + '">' + (data.name || '') + '</td>';
	str += '<td>' + (data.size || '') + '</td>';
	str += '<td>' + (data.weight || '0') + '</td>';
	str += '<td>' + (data.links || '') + '</td>';
	str += '</tr>';

	return $(str);
}

$('a.newtime').live('click', function(e){
	e.preventDefault();

	var tr = buildtime({ links : '<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a></td></tr>'});
	var tds = tr.children();
	$(tds[1]).editbox({name: "name", width: 150});
	$(tds[2]).editbox({name: "move", width: 100});
	$(tds[3]).editbox({name: "game", width: 100});
	$(tds[4]).editbox({name: "sims", width: 100});
	$(tds[5]).editbox({name: "weight", width: 100});

	tr.find('a.save').click(function(e){
		e.preventDefault();
		var input = tr.input_obj();
		$.post("/timesize/savetime", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				data.links = '<a class="edittime" href="#">Edit</a>';
				tr.replaceWith(buildtime(data));
			}
		}, 'json');
	});
	tr.find('a.cancel').click(function(e){
		e.preventDefault();
		tr.remove();
	});
	$('#times').after(tr);
});

$('a.edittime').live('click', function(e){
	e.preventDefault();
	var tr = $(this).parent().parent();
	var tds = tr.children();

	tds.save();

	$(tds[1]).editbox({name: "name", width: 150});
	$(tds[2]).editbox({name: "move", width: 100});
	$(tds[3]).editbox({name: "game", width: 100});
	$(tds[4]).editbox({name: "sims", width: 100});
	$(tds[5]).editbox({name: "weight", width: 100});

	var links = $('<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>');
	links.filter("a.save").click(function(e){
		e.preventDefault();
		var input = tr.input_obj();
		$.post("/timesize/savetime", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				data.links = '<a class="edittime" href="#">Edit</a>';
				tr.replaceWith(buildtime(data));
			}
		}, 'json');
	});
	links.filter("a.cancel").click(function(e){
		e.preventDefault();
		tds.revert();
	});
	$(tds[6]).html(links);
});

$('a.newsize').live('click', function(e){
	e.preventDefault();

	var tr = buildsize({ links : '<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a></td></tr>'});
	var tds = tr.children();
	$(tds[1]).editbox({name: "name", width: 150});
	$(tds[2]).editbox({name: "size", width: 100});
	$(tds[3]).editbox({name: "weight", width: 100});

	tr.find('a.save').click(function(e){
		e.preventDefault();
		var input = tr.input_obj();
		$.post("/timesize/savesize", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				data.links = '<a class="editsize" href="#">Edit</a>';
				tr.replaceWith(buildsize(data));
			}
		}, 'json');
	});
	tr.find('a.cancel').click(function(e){
		e.preventDefault();
		tr.remove();
	});
	$('#sizes').after(tr);
});

$('a.editsize').live('click', function(e){
	e.preventDefault();
	var tr = $(this).parent().parent();
	var tds = tr.children();

	tds.save();

	$(tds[1]).editbox({name: "name", width: 150});
	$(tds[2]).editbox({name: "size", width: 100});
	$(tds[3]).editbox({name: "weight", width: 100});

	var links = $('<a class="save" href="#">Save</a> <a class="cancel" href="#">Cancel</a>');
	links.filter("a.save").click(function(e){
		e.preventDefault();
		var input = tr.input_obj();
		$.post("/timesize/savesize", input, function(data){
			if(data.error){
				alert(data.error);
			}else{
				data.links = '<a class="editsize" href="#">Edit</a>';
				tr.replaceWith(buildsize(data));
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

	<b>Time Limits:</b><br>
	<table>
	<tr id="times">
		<th></td>
		<th>Name</td>
		<th>Per Move</td>
		<th>Per Game</td>
		<th>Simulations</td>
		<th>Weight</td>
		<th><a class="newtime" href="#">New</a></td>
	</tr>
	<? foreach($times as $id => $time){ ?>
		<tr class="l">
		<td><input type="hidden" name="id" value="<?= $id ?>"></td>
		<td><?= $time['name'] ?></td>
		<td><?= $time['move'] ?></td>
		<td><?= $time['game'] ?></td>
		<td><?= $time['sims'] ?></td>
		<td><?= $time['weight'] ?></td>
		<td><a class="edittime" href="#">Edit</a></td>
		</tr>
	<?	} ?>
	</table>

	<br>

	<b>Board Sizes:</b><br>
	<table>
	<tr id="sizes">
		<th></td>
		<th>Name</td>
		<th>Size</td>
		<th>Weight</td>
		<th><a class="newsize" href="#">New</a></td>
	</tr>
	<? foreach($sizes as $id => $size){ ?>
		<tr class="l">
		<td><input type="hidden" name="id" value="<?= $id ?>"></td>
		<td><?= $size['name'] ?></td>
		<td><?= $size['size'] ?></td>
		<td><?= $size['weight'] ?></td>
		<td><a class="editsize" href="#">Edit</a></td>
		</tr>
	<?	} ?>
	</table>

<?
	return true;
}

function timesize_savetime($input, $user){
	global $db;

	if($input['name']){
		if($input['id']){
			$res = $db->pquery("UPDATE times SET name = ?, move = ?, game = ?, sims =  ?, weight = ? WHERE userid = ? && id = ?", 
				$input['name'], $input['move'], $input['game'], $input['sims'], $input['weight'], $user->userid, $input['id']);
		}else{
			$res = $db->pquery("INSERT INTO times SET userid = ?, name = ?, move = ?, game = ?, sims =  ?, weight = ?", 
				$user->userid, $input['name'], $input['move'], $input['game'], $input['sims'], $input['weight']);
			$input['id'] = $res->insertid();
		}
		echo json(h($input));
	}else{
		echo json(array("error" => "empty name"));
	}
	return false;
}

function timesize_savesize($input, $user){
	global $db;

	if($input['name']){
		if($input['id']){
			$res = $db->pquery("UPDATE sizes SET name = ?, size = ?, weight = ? WHERE userid = ? && id = ?", $input['name'], $input['size'], $input['weight'], $user->userid, $input['id']);
		}else{
			$res = $db->pquery("INSERT INTO sizes SET userid = ?, name = ?, size = ?, weight = ?", $user->userid, $input['name'], $input['size'], $input['weight']);
			$input['id'] = $res->insertid();
		}
		echo json(h($input));
	}else{
		echo json(array("error" => "empty name"));
	}
	return false;
}

