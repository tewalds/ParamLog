# A generic GTP player that can set the boardsize and make and generate moves
# Can set time limits but not a simulation limit
# Passes parameters straight through
# Assumes players are white and black and that it is running with linux line endings

require 'players/player.rb'
require 'lib.rb'

class Ataxx < Player
	class_attr_accessor :path, :exec
	attr_class_accessor :path, :exec

	@path = "/home/timo/code/test/asn4/code/src"
	@exec = "ataxx"

	class AtaxxClient
		def initialize(cmdline)
			@io=IO.popen(cmdline,'w+')
			@io.gets "\n\n"
		end
		def cmd(c)
			return "" if c.strip == ""
			puts "> #{c.strip}"
			@io.puts c.strip
			
			ret = ""
			loop{
				line = @io.gets
				break if line.nil? || line == "\n"
				ret += line
			}
			puts "< " + ret.strip.split("\n").join("\n< ")

			return ret
		end
		def close
			@io.close
		end
	end

	def initialize
		cmd = ""
		cmd += path + "/" if path != ""
		cmd += exec
		@c = AtaxxClient.new(cmd)
		@winner = 0
	end
	def quit
		@c.cmd "q"
		@c.close
	end
	def version
		"Timothy's ATAXX!"
	end
	def boardsize(size)
		size = size.to_i

		@c.cmd "i #{size}"

		board = [['e']*size]*size
		board[0][0] = 'w'
		board[0][size-1] = 'b'
		board[size-1][0] = 'b'
		board[size-1][size-1] = 'w'
		@c.cmd "s\n" + board.map{|l| l.join }.join("\n")
	end
	def time(move, game, sims) #ignores sims
		@c.cmd "rt #{game}" if game > 0
		@c.cmd "ft #{move}" if move > 0
		@c.cmd "sim #{sims}" if(sims > 0)
	end
	def params(param)
		return if param == ""
		@c.cmd param
	end
	def play(side, move)
		ret = @c.cmd "m#{move}"

		ret.split("\n").each{|l|
			if(l[0..8] == 'game end:')
				outcome = l.split[-1].to_i
				if(outcome == 0)
					@winner = 3
				elsif(outcome > 0)
					@winner = 1
				elsif(outcome < 0)
					@winner = 2
				end
			end
		}

	end
	def genmove(side)
		return "resign" if @winner > 0
	
		ret = @c.cmd "g"

		move = "pass"

		ret.strip.split("\n").each{|l|
			next if l.strip == ""

			if(l[0..4] == 'move:')
				move = l[5..-1].strip 
			end
			
			if(l[0..8] == 'game end:')
				outcome = l.split[-1].to_i
				if(outcome == 0)
					@winner = 3
				elsif(outcome > 0)
					@winner = 1
				elsif(outcome < 0)
					@winner = 2
				end
			end
		}

		move = "pass" if move == 'a1a1'

		return move
	end
	def winner
		@winner
	end
end

