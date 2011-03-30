# Fuego is a Go program written by Martin Mueller at the University of Alberta

require 'players/gtpplayer.rb'

class Fuego < GTPPlayer
	@path = "~/code/havannah/fuego/fuegomain"
	@exec = "fuego"
	@sides = ['none', 'black', 'white', 'draw']

	def time(move, game, sims)
		move = 10000000 if move == 0 && sims > 0
		@gtp.cmd("time_settings #{game} #{move} 1")
		@gtp.cmd("uct_param_player max_games #{sims}") if sims > 0
	end
	def winner
		case @gtp.cmd("final_score")[2..2]
			when 'B'      then return 1 #first player win
			when 'W'      then return 2 #second player win
			when 'D', '0' then return 3 #draw
			else               return 0 #unknown
		end
	end
end

