# Gambler is a Havannah player written by Richard Pijl

require 'players/gtpplayer.rb'

class Gambler < GTPPlayer
	@path = "~/Desktop/gambler"
	@exec = "gamblerh-w32.exe"
	@sides = ['none', 'white', 'black', 'draw']

	def initialize
		@gtp = GTPClient.new("#{path}/#{exec}", "\r\n")
	end
	def time(move, game, sims)
		@gtp.cmd "time_settings #{game} #{move} #{sims}"
	end
	def params(param)
#		@gtp.cmd "player_params #{param}"
	end
	def winner
		return 0
#		return sides.index(@gtp.cmd("havannah_winner")[2..-1].strip)
	end
end

