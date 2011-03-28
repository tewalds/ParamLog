# A null player that does nothing and can't make moves, but
# works well as a referee if you trust your players

require 'players/player.rb'

class NullPlayer < Player
	def initialize; end
	def quit; end
	def boardsize(size); end
	def time(move, game, sims); end
	def params(param); end
	def play(side, move); end
	def genmove(side)
		return ""
	end
	def winner
		return nil
	end
end

