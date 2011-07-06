<?

function showresults($input, $user){
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

	$times = $db->pquery("SELECT * FROM times WHERE userid = ? ORDER BY name", $user->userid)->fetchrowset();
	$sizes = $db->pquery("SELECT * FROM sizes WHERE userid = ? ORDER BY name", $user->userid)->fetchrowset();
	$numgames = $db->pquery("SELECT count(*) FROM games WHERE userid = ?", $user->userid)->fetchfield();

?>
	<table><form action=/games method=GET>
		<tr>
			<td valign="top" rowspan="2">
				Players:<br>
				<select name=players[] multiple=multiple size=20 style='width: 375px'>
				<? foreach($programs as $pid){
					$player = $players[$pid]; ?>
					<option class="program" value="<?= $pid ?>" disabled="disabled"><?= h($player['name']) ?> (<?= h($player['params']) ?>)</option>
					<? foreach($baselines[$pid] as $bid){
						$player = $players[$bid]; ?>
						<option class="baseline" value="<?= $bid ?>">&nbsp;&nbsp;&nbsp;<?= h($player['name']) ?> (<?= h($player['params']) ?>)</option>
					<? }
					foreach($testgroups[$pid] as $gid){
						$player = $players[$gid]; ?>
						<option class="testgroup" value="<?= $gid ?>">&nbsp;&nbsp;&nbsp;<?= h($player['name']) ?></option>
						<? foreach($testcases[$gid] as $tid){
							$player = $players[$tid]; ?>
							<option class="testcase" value="<?= $tid ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= h($player['name']) ?> (<?= h($player['params']) ?>)</option>
						<? }
					}
				} ?>
				</select>
			</td>
			<td valign="top" colspan="2">
				Baselines: <a class="select_all" ref="baselines" href="#">All</a> | <a class="select_none" ref="baselines" href="#">None</a><br>
				<select id="baselines" name="baselines[]" multiple="multiple" style='width: 500px'>
				<? foreach($programs as $pid){
					$player = $players[$pid]; ?>
					<option class="program" value="<?= $pid ?>" disabled='disabled'><?= h($player['name']) ?> (<?= h($player['params']) ?>)</option>
					<? foreach($baselines[$pid] as $bid){
						$player = $players[$bid]; ?>
						<option class="baseline" value="<?= $bid ?>">&nbsp;&nbsp;&nbsp;<?= h($player['name']) ?> (<?= h($player['params']) ?>)</option>
					<? }
				} ?>
				</select>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Time Limit: <a class="select_all" ref="times" href="#">All</a> | <a class="select_none" ref="times" href="#">None</a><br>
				<select id="times" name="times[]" multiple="multiple" style='width: 300px'>
				<? foreach($times as $time){ ?>
					<option value="<?= $time['id'] ?>"><?= h($time['name']) ?> (<?= h($time['move']) . " " . h($time['game']) . " " . h($time['sims']) ?>)</option>
				<? } ?>
				</select>
				<br><br>
				<?= makeCheckBox("scale", "Scale Graph") /* ?><br>
				<?= makeCheckBox("errorbars", "Show Errorbars") */ ?><br>
				<br>
				<input id='showgraph' type='button' value="Show Graph!">
				<input type='submit' value="Search Games">
			</td>
			<td valign="top">
				Board Sizes: <a class="select_all" ref="sizes" href="#">All</a> | <a class="select_none" ref="sizes" href="#">None</a><br>
				<select id="sizes" name="sizes[]" multiple="multiple" style='width: 195px'>
				<? foreach($sizes as $time){ ?>
					<option value="<?= $time['id'] ?>"><?= h($time['name']) ?> (<?= h($time['size']) ?>)</option>
				<? } ?>
				</select>
				<br><br>
				<?= $numgames ?> games logged
			</td>
		</tr>
	</form></table>

<div id="chart"></div>
<br>
<a id="showshortdata" href="#">Show</a> | <a id="hideshortdata" href="#">Hide</a> Short Data<br>
<div id="shortdata"></div>
<br>
<a id="showlongdata" href="#">Show</a> | <a id="hidelongdata" href="#">Hide</a> Long Data<br>
<div id="longdata"></div>
<br>
<a id="showlatexgraph" href="#">Show</a> | <a id="hidelatexgraph" href="#">Hide</a> Latex Graph<br>
<textarea id="latexgraph" style="width:900px; height: 300px"></textarea>

<script>
$.jqplot.config.enablePlugins = true;

$(function(){
	$('#shortdata').hide();
	$('#longdata').hide();
	$('#latexgraph').hide();

	$('#showshortdata').click(function(e){ e.preventDefault(); $('#shortdata').show(); });
	$('#hideshortdata').click(function(e){ e.preventDefault(); $('#shortdata').hide(); });
	$('#showlongdata').click(function(e){ e.preventDefault(); $('#longdata').show(); });
	$('#hidelongdata').click(function(e){ e.preventDefault(); $('#longdata').hide(); });
	$('#showlatexgraph').click(function(e){ e.preventDefault(); $('#latexgraph').show(); });
	$('#hidelatexgraph').click(function(e){ e.preventDefault(); $('#latexgraph').hide(); });

	$('#showgraph').click(function(e){
		e.preventDefault();

		$.get("/results/data", $('form').serialize(), function(data){
			if(data.error){
				alert(data.error);
			}else{
				data.options.axes.xaxis.renderer = $.jqplot.CategoryAxisRenderer;
				$('#chart').empty().css({height: 500, width: 900});
				$.jqplot('chart', data.data, data.options);

				$('#shortdata').empty();
				var str = '<table width="800">';
				str += '<tr>';
				str += '<th rowspan="2">Player Name</th>';
				str += '<th colspan="' + data.options.axes.xaxis.ticks.length + '">Board size</th>';
				str += '<th rowspan="2">Avg</th>';
				str += '</tr>';
				str += '<tr>';
				for(var i = 0; i < data.options.axes.xaxis.ticks.length; i++)
					str += '<th>' + data.options.axes.xaxis.ticks[i] + '</th>';
				str += '</tr>';

				for(var i = 1; i < data.data.length; i++){
					str += '<tr class="l">'
					str += '<td>' + data.options.series[i].label + '</td>';
					total = 0;
					for(var j = 0; j < data.data[i].length; j++){
						str += '<td align="right">' + data.data[i][j][1].toFixed(2) + '</td>';
						total += data.data[i][j][1];
					}
					str += '<td align="right">' + (total/data.data[i].length).toFixed(2) + '</td>';
					str += '</tr>';
				}
				str += "</table>";
				$('#shortdata').html(str);

				$('#longdata').empty();
				var str = '<table width="800">';
				str += '<tr>';
				str += '<th rowspan="2">Player Name</th>';
				str += '<th rowspan="2">Board Size</th>';
				str += '<th colspan="4">Outcome</th>';
				str += '<th colspan="4">Games</th>';
				str += '</tr>';
				str += '<tr>';
				str += '<th>Win rate</th>';
				str += '<th>High</th>';
				str += '<th>Low</th>';
				str += '<th>95% Error</th>';
				str += '<th>Wins</th>';
				str += '<th>Losses</th>';
				str += '<th>Ties</th>';
				str += '<th>Total</th>';
				str += '</tr>';

				for(var i = 1; i < data.data.length; i++){
					for(var j = 0; j < data.data[i].length; j++){
						str += '<tr class="' + (j == 0 ? 'l2' : 'l') + '">'
						str += '<td>' + (j == 0 ? data.options.series[i].label : '') + '</td>';
						str += '<td>' + data.options.axes.xaxis.ticks[j] + '</td>';
						for(var k = 1; k < data.data[i][j].length; k++)
							str += '<td align="right">' + data.data[i][j][k].toFixed((k < 5 ? 2 : 0)) + '</td>';
						str += '</tr>';
					}
				}
				str += "</table>";
				$('#longdata').html(str);

				$('#latexgraph').empty();
				var str = "\\begin{tikzpicture}\n" +
					"\\begin{axis}[\n" +
					"	xlabel=Board Size,\n" +
					"	ylabel=Win Rate (\\%),\n" +
					"	xtick={" + data.options.axes.xaxis.ticks.join(',') + "},\n" +
					"	ymin=0, ymax=100,\n" +
					"	minor y tick num=1,\n" +
					"	extra y ticks={50},\n" +
					"	extra y tick style={grid=major},\n" +
					"]\n";

				for(var i = 1; i < data.data.length; i++){
					str += "\\addplot coordinates { ";
					for(var j = 0; j < data.data[i].length; j++){
						str += "(" + data.options.axes.xaxis.ticks[j] + "," + data.data[i][j][1].toFixed(2) + ") ";
					}
					str += "}; \\addlegendentry{" + data.options.series[i].label + "}\n";
				}
				str += "\\end{axis}\n";
				str += "\\end{tikzpicture}\n";
				$('#latexgraph').val(str);
			}
		}, 'json');
	});

	$('a.select_all').click(function(e){
		e.preventDefault();
		$("#" + $(this).attr('ref')).children("[disabled=false]").attr('selected', 'selected');
	});
	$('a.select_none').click(function(e){
		e.preventDefault();
		$("#" + $(this).attr('ref')).children().attr('selected', '');
	});
});
</script>

<?

	return true;
}


function getdata($input, $user){
	global $db;


	if(empty($input['players']) || empty($input['baselines']) || empty($input['sizes']) || empty($input['times']))
		return json_error("You must select options from all categories to see any results!");


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

	$ids = array_merge($input['players'], $input['baselines']);
	$baselineids = array_combine($input['baselines'], $input['baselines']);
	$playerids = array_combine($input['players'], $input['players']);

	$res = $db->pquery("SELECT * FROM results WHERE userid = ? && player1 IN ? && player2 IN ? && time IN ? && size IN ?",
			$user->userid, $ids, $ids, $input['times'], $input['sizes']);

	$rawdata = array();
	while($line = $res->fetchrow()){
		if(isset($baselineids[$line['player1']]) && isset($playerids[$line['player2']])){
			$line['name'] = $players[$line['player2']]['name'];
			$rawdata[] = $line;
		}

		if(isset($baselineids[$line['player2']]) && isset($playerids[$line['player1']])){
			swap($line['player1'], $line['player2']);
			swap($line['p1wins'], $line['p2wins']);
			$line['name'] = $players[$line['player2']]['name'];
			$rawdata[] = $line;
		}
	}

	usort($rawdata, 'cmpname');

	$sizes = $db->pquery("SELECT id, size FROM sizes WHERE userid = ? && id IN ? ORDER BY name", $user->userid, $input['sizes'])->fetchfieldset();

	$defaults = array();
	foreach($sizes as $s => $n)
		$defaults[$s] = array('wins' => 0, 'loss' => 0, 'ties' => 0, 'total' => 0, 'rate' => 0, 'err' => 0, 'lb' => 0, 'ub' => 0);

	$data = array(); //	{ playerid => {size => [wins, loss, ties, etc] } }
	foreach($rawdata as $line){
		undefset($data[$line['player2']], $defaults);

		$data[$line['player2']][$line['size']]['wins'] += $line['p2wins'];
		$data[$line['player2']][$line['size']]['loss'] += $line['p1wins'];
		$data[$line['player2']][$line['size']]['ties'] += $line['ties'];
	}

	$lbound = 0.5;
	$ubound = 0.5;
	foreach($data as $p => $data2){
		foreach($data2 as $s => $row){
			$row['total'] = $row['wins'] + $row['loss'] + $row['ties'];
			$row['rate']  = ($row['total'] > 0 ? ($row['wins'] + $row['ties']/2.0)/$row['total'] : 0);
			$row['err']   = ($row['total'] > 0 ? 1.96*sqrt($row['rate']*(1-$row['rate'])/$row['total']) : 0);
			$row['lb']    = max(0, $row['rate'] - $row['err']);
			$row['ub']    = min(1, $row['rate'] + $row['err']);
			$data[$p][$s] = $row;

			if($lbound > $row['lb']) $lbound = $row['lb'];
			if($ubound < $row['ub']) $ubound = $row['ub'];
		}
	}

	if($input['scale']){
		$diff = $ubound - $lbound;
		$interval = ($diff <= 0.1 ? 0.01 : ($diff <= 0.2 ? 0.02 : ($diff <= 0.6 ? 0.05 : 0.1)));
		$lbound = max(0, floor($lbound/$interval)*$interval);
		$ubound = min(1, ceil($ubound/$interval)*$interval);
	}else{
		$interval = 0.1;
		$lbound = 0;
		$ubound = 1;
	}


	$options = array(
		'seriesDefaults' => array('shadow' => false),
		'series' => array(),
		'legend' => array('show' => true),
		'axes'   => array(
			'yaxis' => array(
				'min' => $lbound*100,
				'max' => $ubound*100,
				'ticks' => range($lbound*100, $ubound*100, $interval*100),
			),
			'xaxis' => array(
				'show' => true,
//				'renderer' => "$.jqplot.CategoryAxisRenderer", //must be done after the fact, since json can't reference the actual object
				'ticks' => array_values($sizes),
			),
		),
		'highlighter' => array(
			'tooltipAxes' => 'y',
			'yvalues' => 8,
			'formatString' => '<table class="jqplot-highlighter">' .
				'<tr><td>avg:</td><td>%.2f</td></tr>' .
				'<tr><td>hi:</td><td>%.2f</td></tr>' .
				'<tr><td>low:</td><td>%.2f</td></tr>' .
				'<tr><td>95%:</td><td>%.2f</td></tr>' .
				'<tr><td>wins:</td><td>%d</td></tr>' .
				'<tr><td>losses:</td><td>%d</td></tr>' .
				'<tr><td>ties:</td><td>%d</td></tr>' .
				'<tr><td>total:</td><td>%d</td></tr>' .
				'</table>',
		),
	);
	$output = array();
	$output[] = array_fill(0, count($sizes), 50);
	$options['series'][] = array('color' => "#FF0000", "lineWidth" => 1, 'showMarker' => false, 'label' => ' ');

	foreach($data as $p => $data2){
		$line = array();
		$i = 0;
		foreach($data2 as $s => $row)
//			$line[] = round($row['rate']*100, 2);
			$line[] = array(++$i, round($row['rate']*100,3), round($row['ub']*100,3), round($row['lb']*100,3), round($row['err']*100, 3), $row['wins'], $row['loss'], $row['ties'], $row['total']);
		$output[] = $line;
		$options['series'][] = array('label' => h($players[$p]['name']));
	}

	echo json(array('options' => $options, 'data'=> $output));
	return false;
}

