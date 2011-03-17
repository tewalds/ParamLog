
class Gambler < Player
	@@path = "~/Desktop/gambler"
	@@exec = "gamblerh-w32.exe"
	@@sides = ['none', 'white', 'black', 'draw']

	def initialize(exec = nil)
		exec ||= "#{@@path}/#{@@exec}"
		@gtp = GTPClient.new(exec, "\r\n")
	end
	def quit
		@gtp.cmd "quit"
		@gtp.close
	end
	def time(move, game, sims)
		@gtp.cmd "time_settings #{game} #{move} #{sims}"
	end
	def params(param)
#		@gtp.cmd "player_params #{param}"
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
		return 0
#		return @@sides.index(@gtp.cmd("havannah_winner")[2..-1].strip)
	end
end

