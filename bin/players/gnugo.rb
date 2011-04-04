# GnuGo is a Go program

require 'players/gtpplayer.rb'

class Gnugo < GTPPlayer
	@path = ""
	@exec = "gnugo --mode gtp"
	@sides = ['none', 'black', 'white', 'draw']

	def winner
		case @gtp.cmd("final_score")[1][0..0]
			when 'B'      then return 1 #first player win
			when 'W'      then return 2 #second player win
			when 'D', '0' then return 3 #draw
			else               return 0 #unknown
		end
	end
end

