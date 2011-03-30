# Base class for all players to implement, must replace all the methods

class Player
	#run a benchmark to gauge the speed of this computer
	def self.benchmark
		fib(34)
	end
	#how long should the benchmark take, in seconds?
	def self.benchtime
		return 10
	end

	#create the object, might start the program
	def initialize
		raise "Unimplemented initialize method"
	end

	#shutdown the program
	def quit
		raise "Unimplemented quit method"
	end

	#set the boardsize
	def boardsize(size)
		raise "Unimplemented boardsize method"
	end

	#set the time limits for the game, in time per move, time per game, and simulations per move
	def time(move, game, sims)
		raise "Unimplemented time method"
	end

	#set the program parameters in a program specific way
	def params(param)
		raise "Unimplemented params method"
	end

	#done initialization, start the program or a no-op if already done
	def start
	end

	#play a move that the other player generated
	def play(side, move)
		raise "Unimplemented play method"
	end

	#generate a move, return either a move string or a hash of results including a move string
	def genmove(side)
		raise "Unimplemented genmove method"
		return "move"
		return {:move => "move", :value => "float", :outcome => "solved outcome", :work => "num simulations", :comment => "" }
	end

	#return the winner, one of 0 for draw, 1,2 for players and 3 for draw
	def winner
		raise "Unimplemented winner method"
		return 0
	end
end

