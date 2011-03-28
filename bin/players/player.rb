# Base class for all players to implement, must replace all the methods

class Player
	def self.benchtime
		return 10
	end
	def self.benchmark
		fib(34)
	end
	def initialize
		raise "Unimplemented initialize method"
	end
	def quit
		raise "Unimplemented quit method"
	end
	def boardsize(size)
		raise "Unimplemented boardsize method"
	end
	def time(move, game, sims)
		raise "Unimplemented time method"
	end
	def params(param)
		raise "Unimplemented params method"
	end
	def play(side, move)
		raise "Unimplemented play method"
	end
	def genmove(side)
		raise "Unimplemented genmove method"
		return "move"
		return {:move => "move", :value => "float", :outcome => "solved outcome", :work => "num simulations", :comment => "" }
	end
	def winner #return the winner, one of none, draw, black, white
		raise "Unimplemented winner method"
		return "none"
	end
end

