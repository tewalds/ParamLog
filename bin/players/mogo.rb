# Mogo is a Go player written by Sylvain Gelly and others

require 'players/gtpplayer.rb'

class Mogo < GTPPlayer
	@path = "~/Desktop/MoGo_release3"
	@exec = "mogo"
	@sides = ['none', 'black', 'white', 'draw']

	def initialize
		@time = {:move => 0, :game => 120, :sims => 0}
		@params = ""
		@gtp = nil
	end
	def boardsize(size, start)
		@boardsize = size
	end
	def time(move, game, sims)
		@time[:move] = move
		@time[:game] = game
		@time[:sims] = sims
	end
	def params(param)
		@params = param
	end
	def start
		cmd = "#{path}/#{exec}"
		cmd += " --#{@boardsize}"
		cmd += " --time #{@time[:move]}"               if @time[:move] > 0
		cmd += " --totalTime #{@time[:game]}"          if @time[:game] > 0
		cmd += " --nbTotalSimulations #{@time[:sims]}" if @time[:sims] > 0
		cmd += " --useOpeningDatabase 0"
		cmd += " --dontDisplay 1"
		cmd += " #{@params}" if @params.strip != ""

		puts "> #{cmd}"
		@gtp = GTPClient.new(cmd)
		@gtp.cmd "boardsize #{@boardsize}"
	end
	def winner
		return 0
	end
end

