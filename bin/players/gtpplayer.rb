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
	def version
		n = @gtp.cmd "name"
		v = @gtp.cmd "version"

		parts = []
		parts << n[1] if n[0]
		parts << v[1] if v[0]
		return parts.join(' ')
	end
	def boardsize(size, start)
		r = @gtp.cmd "boardsize #{size}"
		raise "GTP command failed: boardsize #{size}\n#{r[1]}" if !r[0]
	end
	def time(move, game, sims) #ignores sims
		r = @gtp.cmd "time_settings #{game} #{move} 1"
		raise "GTP command failed: time_settings #{game} #{move} 1\n#{r[1]}" if !r[0]
	end
	def params(param)
		if param.strip != ""
			r = @gtp.cmd param
			raise "GTP command failed: param\n#{r[1]}" if !r[0]
		end
	end
	def play(side, move)
		r = @gtp.cmd "play #{sides[side]} #{move}"
		raise "GTP command failed: play #{sides[side]} #{move}\n#{r[1]}" if !r[0]
	end
	def genmove(side)
		r = @gtp.cmd("genmove #{sides[side]}")
		raise "GTP command failed: genmove #{sides[side]}\n#{r[1]}" if !r[0]
		return r[1].split[0]
	end
end

