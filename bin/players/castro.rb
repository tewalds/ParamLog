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
		@gtp.cmd "extended 1"
#		@gtp.cmd "verbose 2"
	end
	def time(move, game, sims)
		@gtp.cmd "time -m #{move} -g #{game} -r #{game} -i #{sims} -f 0"
	end
	def params(param)
		@gtp.cmd "player_params #{param}"
	end
	def genmove(side)
		res = @gtp.cmd("genmove #{sides[side]}")[1].split

		return res[0] if(res.length == 1)

		#translate to this format of outcome
		res[2] = res[2].to_i
		res[2] = 3 if(res[2] == 0)
		res[2] = 0 if(res[2] < 0)

		return {"position" => res[0], "value" => res[1], "outcome" => res[2], "work" => res[3], "nodes" => res[4]}
	end
	def winner
		return sides.index(@gtp.cmd("havannah_winner")[1])
	end
end

