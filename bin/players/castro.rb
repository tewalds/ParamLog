# Castro is a Havannah player written by Timo Ewalds

require 'players/gtpplayer.rb'

class Castro < GTPPlayer
	@path = "~/code/castro"
	@exec = "castro"
	@sides = ['none', 'white', 'black', 'draw']

	def self.benchmark
		`#{path}/#{exec} -f #{path}/test/speed4.tst`
	end
	def self.benchtime
		return 11
	end

	def initialize
		super
		@gtp.cmd "hguicoords"
	end
	def time(move, game, sims)
		@gtp.cmd "time -m #{move} -g #{game} -r #{game} -i #{sims} -f 0"
	end
	def params(param)
		@gtp.cmd "player_params #{param}"
	end
	def winner
		return sides.index(@gtp.cmd("havannah_winner")[1])
	end
end

