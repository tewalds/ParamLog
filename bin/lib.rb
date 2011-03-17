
def timer
	start = Time.now
	yield
	return Time.now - start
end

class GTPClient
	def initialize(cmdline, newline = "\n")
		@io=IO.popen(cmdline,'w+')
		@sep = newline + newline
	end
	def cmd(c)
		return "" if c.strip == ""
		@io.puts c.strip
		return @io.gets(@sep)
	end
	def close
		@io.close
	end
end

def fib(a)
	return a if a <= 1
	return fib(a-1) + fib(a-2)
end

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

class GTPPlayer < Player
	def initialize(exec)
		@gtp = GTPClient exec
	end
	def quit
		@gtp.cmd "quit"
		@gtp.close
	end
	def boardsize(size)
		@gtp.cmd "boardsize #{size}"
	end
	def play(side, pos)
		@gtp.cmd "play #{side} #{pos}"
	end
	def genmove(side)
		@gtp.cmd("genmove #{side}")[2..-1].strip;
	end
end


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

def scaletime(expected, &block)
	benchtime = timer { block.call }

	abort "benchmark time in scaletime < 0.1, assuming it failed to run\n" if(benchtime < 0.1)

	return benchtime / expected
end

class Float
	def round(digits = 0)
		if(digits > 0)
			p = (10**digits).to_f
			f = self * p
			return (f+0.5).floor/p if f > 0.0
			return (f-0.5).ceil/p  if f < 0.0
			return 0.0
		else
			return (self+0.5).floor if self > 0.0
			return (self-0.5).ceil  if self < 0.0
			return 0
		end
	end
end

class Array
	#map_fork runs the block once for each value, each in it's own process.
	# It takes a maximum concurrency, or nil for all at the same time
	# Clearly the blocks can't affect anything in the parent process.
	def map_fork(concurrency, &block)
		if(concurrency == 1)
			return self.map(&block);
		end

		children = []
		results = []
		socks = {}

		#create as a proc since it is called several times, 
		# but is not useful outside of this function, and needs the local scope.
		read_socks_func = proc {
			while(socks.length > 0 && (readsocks = IO::select(socks.keys, nil, nil, 0.1)))
				readsocks.first.each{|sock|
					rd = sock.read
					if(rd.nil? || rd.length == 0)
						results[socks[sock]] = Marshal.load(results[socks[sock]]);
						socks.delete(sock);
					else
						results[socks[sock]] ||= ""
						results[socks[sock]] += rd
					end
				}
			end
		}

		self.each_with_index {|val, index|
			rd, wr = IO.pipe

			children << fork {
				rd.close
				result = block.call(val)
				wr.write(Marshal.dump(result))
				wr.sync
				wr.close
				exit;
			}

			wr.close
			socks[rd] = index;

			#if needed, wait for a previous process to exit
			if(concurrency)
				begin
					begin
						read_socks_func.call
		
						while(pid = Process.wait(-1, Process::WNOHANG))
							children.delete(pid);
						end
					end while(children.length >= concurrency && sleep(0.1))
				rescue SystemCallError
					children = []
				end
			end
		}

		#wait for all processes to finish before returning
		begin
			begin
				read_socks_func.call

				while(pid = Process.wait(-1, Process::WNOHANG))
					children.delete(pid);
				end
			end while(children.length >= 0 && sleep(0.1))
		rescue SystemCallError
			children = []
		end


		read_socks_func.call

		return results
	end
end


def loop_fork(concurrency, &block)
	if(concurrency == 1)
		loop &block
	end

	children = []

	loop {
		children << fork {
			loop {
				block.call
			}
			exit;
		}

		#if needed, wait for a previous process to exit
		if(concurrency)
			begin
				begin
					while(pid = Process.wait(-1, Process::WNOHANG))
						children.delete(pid);
					end
				end while(children.length >= concurrency && sleep(0.1))
			rescue SystemCallError
				children = []
			end
		end
	}

	#wait for all processes to finish before returning
	begin
		begin
			while(pid = Process.wait(-1, Process::WNOHANG))
				children.delete(pid);
			end
		end while(children.length > 0 && sleep(0.1))
	rescue SystemCallError
		children = []
	end

	return self
end

