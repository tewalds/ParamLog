#!/usr/bin/ruby

	require 'lib.rb'
	#load all players
	Dir.open('players').each{|file|
		require "players/#{file}" if file[-3..-1] == '.rb'
	}

	$parallel = 1;
	$url = "http://paramlog.ewalds.ca";
	$apikey = ""
	$benchplayer = Player
	$referee = NullPlayer
	$refeverymove = false
	$maxmoves = 1000
	$finishmoves = ['resign', 'none', 'unknown']
	$startstates = {}

	loadconfig = false
	while(ARGV.length > 0)
		arg = ARGV.shift
		case arg
		when "-p", "--parallel"  then $parallel = ARGV.shift.to_i
		when "-h", "--help"      then
			puts "Run a worker process that plays games between players"
			puts "based on a database of players, baselines, sizes and times"
			puts "Usage: #{$0} [<options>] <config file>"
			puts "  -p --parallel   Number of games to run in parallel [#{$parallel}]"
			puts "  -h --help       Print this help"
			exit;
		else
			if(loadconfig)
				puts "Unknown argument #{arg}"
				exit;
			else
				require arg
				loadconfig = true
			end
		end
	end

	if(!loadconfig)
		puts "No config file specified"
		exit
	end


require 'net/http'
require 'json'

log "benchmarking..."
time_factor = scaletime($benchplayer.benchtime){ $benchplayer.benchmark }
log "time_factor: " + time_factor.inspect


n = 0;
loop_fork($parallel) {
	n += 1;
	players = [nil, nil, nil] #ref, p1, p2
	begin
		game = JSON.parse(Net::HTTP.get(URI.parse("#{$url}/api/getwork?apikey=#{$apikey}")));

		log "New game, params: " + game.inspect

		raise game['error'] if(game['error'])

		game['timemove'] *= time_factor
		game['timegame'] *= time_factor

		players[0] = $referee.new
		players[1] = Object.const_get(game['p1cmd']).new
		players[2] = Object.const_get(game['p2cmd']).new

		#choose a startstate if there is a list of them for this size
		starts = $startstates[game['sizeparam']]
		startstate = starts && starts[rand(starts.size)]

		players.each{|p|
			p.time(game['timemove'], game['timegame'], game['timesims'])
			p.boardsize(game['sizeparam'], startstate)
		}

		players[1].params(game['p1config'])
		players[2].params(game['p2config'])
		players[2].params(game['p2test'])

		players.each{|p| p.start }

		versions = players.map{|p| p.version}

		turn = rand(2) + 1; #which player is making the move
		side = 1;           #which side is the player making the move for
		i = 1;
		move = nil;
		gamelog = []
		passes = 0; #game is over if there are two pass moves in a row
		moveresult = {"movenum" => 0, "position" => "", "side" => 0, "value" => 0, "outcome" => 0, "timetaken" => 0, "work" => 0, "nodes" => 0, "comment" => ""}
		totaltime = timer {
			loop{
				$0 = "Game #{n} move #{i}, size #{game['sizeparam']}"

				#ask for a move
				log "Game #{n} move #{i}, size #{game['sizeparam']}: genmove #{side}";
				time = timer {
					move = players[turn].genmove(side)
				}

				entry = moveresult.dup
				entry['movenum']   = i
				entry['side']      = turn
				entry['timetaken'] = time

				if(move.class == String)
					entry['position'] = move
				else
					entry.merge! move
					raise "Invalid move, must define 'position' value" if !entry['position'] || entry['position'] == ''
				end

				o = entry['outcome'];
				entry['outcome'] = (turn != side && (o == 1 || o == 2) ? 3 - o : o)


				log "Game #{n} move #{i}, size #{game['sizeparam']}: play #{side} #{entry['position']}";
				gamelog << entry

				m = entry['position']

				#game over if one player resigns or both pass in succession
				passes = (m.downcase == 'pass' ? passes + 1 : 0)
				break if $finishmoves.index(m.downcase) || passes >= 2

				#pass the move to the other player
				players[3-turn].play(side, m)
				players[0].play(side, m)

				#game over if the ref says so or pass the move limit
				outcomeref = ($refeverymove ? players[0].winner : 0)
				break if outcomeref != 0 || i >= $maxmoves

				i += 1;
				turn = 3-turn;
				side = 3-side;
			}
		}

		outcome = players.map{|p|
			o = p.winner
			(turn != side && (o == 1 || o == 2) ? 3 - o : o)
		}

		players.each{|p|
			p.quit
		}

		#save the game
		result = {
			"apikey"  => $apikey,
			"player1" => game['p1id'],
			"player2" => game['p2id'],
			"size"    => game['sizeid'],
			"time"    => game['timeid'],
			"outcome1" => outcome[1],
			"outcome2" => outcome[2],
			"outcomeref" => outcome[0],
			"version1" => versions[1],
			"version2" => versions[2],
			"host" => `hostname`.strip,
			"saveresult" => true,
			"jsonmoves" => gamelog.to_json,
		}
		Net::HTTP.post_form(URI.parse("#{$url}/api/savegame"), result);
	rescue
		puts "An error occurred: #{$!}"
		puts $@
		players[0].quit if players[0]
		players[1].quit if players[1]
		players[2].quit if players[2]
		sleep(10);
	end
}

