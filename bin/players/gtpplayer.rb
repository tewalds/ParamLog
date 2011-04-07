# A generic GTP player that can set the boardsize and make and generate moves
# Can set time limits but not a simulation limit
# Passes parameters straight through
# Assumes players are white and black and that it is running with linux line endings

require 'players/player.rb'
require 'lib.rb'

class GTPPlayer < Player
	class_attr_accessor :path, :exec, :sides
	attr_class_accessor :path, :exec, :sides

	@path = "path/to/player/directory"
	@exec = "player_name"
	@sides = ['none', 'white', 'black', 'draw']

	def initialize
		cmd = ""
		cmd += path + "/" if path != ""
		cmd += exec
		@gtp = GTPClient.new cmd
	end
	def quit
		@gtp.cmd "quit"
		@gtp.close
	end
	def boardsize(size)
		@gtp.cmd "boardsize #{size}"
	end
	def time(move, game, sims) #ignores sims
		@gtp.cmd "time_settings #{game} #{move} 1"
	end
	def params(param)
		@gtp.cmd param if param.strip != ""
	end
	def play(side, move)
		@gtp.cmd "play #{sides[side]} #{move}"
	end
	def genmove(side)
		return @gtp.cmd("genmove #{sides[side]}")[1].split[0]
	end
end

