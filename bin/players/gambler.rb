# Gambler is a Havannah player written by Richard Pijl

require 'players/gtpplayer.rb'

class Gambler < GTPPlayer
	@path = "/home/timo/Desktop/gambler"
	@exec = "gamblerh-w32.exe"
	@sides = ['none', 'white', 'black', 'draw']


	def initialize
		@time = {:move => 0, :game => 120, :sims => 0}
		@params = ""
		@gtp = nil
	end
	def boardsize(size)
		@boardsize = size
	end
	def time(move, game, sims)
		@time[:move] = move
		@time[:game] = game
		@time[:sims] = sims
	end
	def params(param)
		@params += param + "\r\n"
	end
	def start
		@filename = "/tmp/gambler.#{rand 1000000000}.ini"
		config = File.open("#{path}/gambler.ini"){|f| f.read }
		config += "\r\n" + @params
		File.open(@filename, "w"){|f| f.write config }

		cmd = "#{path}/#{exec} #{@filename[0..-5]}"
		puts "> #{cmd}"
		@gtp = GTPClient.new(cmd, "\r\n")
		@gtp.cmd "boardsize #{@boardsize}"
		@gtp.cmd "time_settings #{@time[:game]} #{@time[:move]} 1"
	end
	def quit
		@gtp.cmd "quit"
		@gtp.close
		File.delete(@filename) if File.exists?(@filename)
	end
	def winner
		return 0
	end
end

