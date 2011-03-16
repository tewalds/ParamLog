
class Castro < Player
	@@path = "~/code/castro"
	@@exec = "castro"
	@@sides = ['none', 'white', 'black', 'draw']

	def self.benchmark
		`#{@@path}/#{@@exec} -f #{@@path}/test/speed4.tst`
	end
	def self.benchtime
		return 11
	end

	def initialize(exec = nil)
		exec ||= "#{@@path}/#{@@exec}"
		@gtp = GTPClient.new exec
	end
	def quit
		@gtp.cmd "quit"
		@gtp.close
	end
	def time(move, game, sims)
		@gtp.cmd "time -m #{move} -g #{game} -r #{game} -i #{sims} -f 0"
	end
	def params(param)
		@gtp.cmd "player_params #{param}"
	end
	def boardsize(size)
		@gtp.cmd "boardsize #{size}"
	end
	def play(side, pos)
		@gtp.cmd "play #{@@sides[side]} #{pos}"
	end
	def genmove(side)
		return @gtp.cmd("genmove #{@@sides[side]}")[2..-1].strip
#		return {:move => "move", :value => "float", :outcome => "solved outcome", :work => "num simulations", :comment => "" }
	end
	def winner
		return @@sides.index(@gtp.cmd("havannah_winner")[2..-1].strip)
	end
end

