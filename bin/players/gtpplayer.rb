# A generic GTP player that can set the boardsize and make moves, 
# but can't set parameters, set time limits nor decide the winner
# Assumes players are white and black

require 'players/player.rb'
require 'lib.rb'

class GTPPlayer < Player
	class_attr_accessor :path, :exec, :sides
	attr_class_accessor :path, :exec, :sides

	@path = "path/to/player/directory"
	@exec = "player_name"
	@sides = ['none', 'white', 'black', 'draw']

	def initialize
		@gtp = GTPClient.new("#{path}/#{exec}")
	end
	def quit
		@gtp.cmd "quit"
		@gtp.close
	end
	def boardsize(size)
		@gtp.cmd "boardsize #{size}"
	end
	def play(side, move)
		@gtp.cmd "play #{sides[side]} #{move}"
	end
	def genmove(side)
		return @gtp.cmd("genmove #{sides[side]}")[2..-1].strip
	end
end

