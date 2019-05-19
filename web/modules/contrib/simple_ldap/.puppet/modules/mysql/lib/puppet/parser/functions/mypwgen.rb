#
# mypwgen.rb
#

module Puppet::Parser::Functions
  newfunction(:mypwgen, :type => :rvalue, :doc => <<-EOS
Generates a random string of the given length appropriate for use as a password.
    EOS
  ) do |args|

    srand(Time.now.to_i)

    if args.size >= 1
      length = args[0].to_i
    else
      length = 10
    end

    o = [('a'..'z'),('A'..'Z'),('0'..'9')].map{|i| i.to_a}.flatten; 
    password = (0..length).map{ o[rand(o.length)] }.join;

    return password

  end
end
