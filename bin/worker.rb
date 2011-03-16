#!/usr/bin/ruby

	require 'lib.rb'
	#load all players
	Dir.open('players').each{|file|
		require "players/#{file}" if file[-3..-1] == '.rb'
	}

	$parallel = 1;
	$url = "http://paramlog.ewalds.ca";
	$apikey = "1bab1eae9a58b147de6f7518c6644a38"
	$benchplayer = Castro
	$referee = Castro
	$maxmoves = 1000

	while(ARGV.length > 0)
		arg = ARGV.shift
		case arg
		when "-p", "--parallel"  then $parallel = ARGV.shift.to_i;
		when "-h", "--help"      then
			puts "Run a worker process that plays games between players that understand GTP"
			puts "based on a database of players, baselines, sizes and times"
			puts "Usage: #{$0} [<options>]"
			puts "  -p --parallel   Number of games to run in parallel [#{$parallel}]"
			puts "  -h --help       Print this help"
			exit;
		else
			puts "Unknown argument #{arg}"
			exit;
		end
	end


require 'net/http'
require 'json'

puts "benchmarking..."
time_factor = scaletime($benchplayer.benchtime){ $benchplayer.benchmark }
puts "time_factor: " + time_factor.inspect


n = 0;
loop_fork($parallel) {
	n += 1;
	players = [nil, nil, nil] #ref, p1, p2
	begin
		game = JSON.parse(Net::HTTP.get(URI.parse("#{$url}/api/getwork?apikey=#{$apikey}")));

puts game.inspect

		game['timemove'] *= time_factor
		game['timegame'] *= time_factor

		players[0] = $referee.new
		players[1] = Object.const_get(game['p1cmd']).new
		players[2] = Object.const_get(game['p2cmd']).new

		players.each{|p|
			p.time(game['timemove'], game['timegame'], game['timesims'])
			p.boardsize(game['sizeparam'])
		}

		players[1].params(game['p1config'])
		players[2].params(game['p2config'])
		players[2].params(game['p2test'])

		turn = 1;           #which player is making the move
		side = rand(2) + 1; #which side is the player making the move for
		i = 1;
		move = nil;
		log = []
		moveresult = {"movenum" => 0, "position" => "", "side" => 0, "value" => 0, "outcome" => 0, "timetaken" => 0, "work" => 0, "comment" => ""}
		totaltime = timer {
			loop{
				$0 = "Game #{n} move #{i}, size #{game['sizeparam']}"

				#ask for a move
				print "genmove #{side}: ";
				time = timer {
					move = players[turn].genmove(side)
				}

				entry = moveresult.dup
				entry['movenum']   = i
				entry['side']      = side
				entry['timetaken'] = time

				if(move.class == String)
					entry['position'] = move
				else
					entry.merge! move
					raise "Invalid move, must define 'position' value" if !entry['position'] || entry['position'] == ''
				end

				puts entry['position']
				log << entry

				m = entry['position']
				break if m == "resign" || m == "none" || m == "unknown" || i >= $maxmoves

				#pass the move to the other player
				players[3-turn].play(side, move)
				players[0].play(side, move)

				i += 1;
				turn = 3-turn;
				side = 3-side;
			}
		}

		outcomeref = players[0].winner
		outcome1   = players[1].winner
		outcome2   = players[2].winner

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
			"outcome1" => outcome1,
			"outcome2" => outcome2,
			"outcomeref" => outcomeref,
		}
		res = Net::HTTP.post_form(URI.parse("#{$url}/api/savegame"), result);
		savegame = JSON.parse(res.body)

		#save the moves
		log.each{|entry|
			entry.merge!({ "apikey"  => $apikey, "gameid" => savegame['id'] })
			Net::HTTP.post_form(URI.parse("#{$url}/api/addmove"), entry);
		}

		#save the result
		result = {
			"apikey"  => $apikey,
			"player1" => game['p1id'],
			"player2" => game['p2id'],
			"size"    => game['sizeid'],
			"time"    => game['timeid'],
			"outcome" => outcomeref || (outcome1 == outcome2 ? outcome1 : 0), #0 is unknown, 1 is p1, 2 is p2, 3 is draw
		}
		if(result['outcome'] != 0)
			Net::HTTP.post_form(URI.parse("#{$url}/api/saveresult"), result);
		end
	rescue
		puts "An error occurred: #{$!}"
		puts $@
		players[0].quit if players[0]
		players[1].quit if players[1]
		players[2].quit if players[2]
		sleep(1);
	end
}

