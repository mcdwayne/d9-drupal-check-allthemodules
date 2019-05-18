#! /usr/bin/env ruby
#
# Example of a non-Drupal (Ruby) XML-RPC client for the 
# {G2 Glossary module}[http://drupal.org/project/g2]
#
# It can connect to G2 version 4.6, 4.7, 5.x, and 6.x, and includes a
# way to differentiate between old (<= 5.x) and 6.x servers, thanks to
# the api() method.
#
# @copyright Copyright (C) 2005-2011 Frederic G. MARAND for Ouest Systèmes Informatiques (OSInet, OSI)
#
# @license Licensed under the CeCILL, version 2 and General Public License version 2 or later
#
# License note: G2 is distributed by OSInet to its customers under the
# CeCILL 2.0 license. OSInet support services only apply to the module
# when distributed by OSInet, not by any third-party further down the
# distribution chain.
#
# If you obtained G2 from drupal.org, that site received it under the General
# Public License version 2 or later (GPLv2+) and can therefore distribute it
# under the same terms, and so can you and just anyone down the chain as long
# as the GPLv2+ terms are abided by, the module distributor in that case being
# the drupal.org organization or the downstream distributor, not OSInet.

require 'xmlrpc/client' # This is needed to use XML-RPC
require 'yaml'          # Needed for the to_yaml() methods at the end

#  A simple XML-RPC client aware of the XML-RPC versions in the 
#  Drupal 6 version of the G2 Glossary.
class G2_client
  
  # client constructor:
  # sets up the host and list of allowed methods, both actual (api) and
  # simulated (all the other ones).
  def initialize(host)
    @host = host || 'localhost'
    @methods = %w[api alphabar latest random stats top wotd]
  end
  
  # api() was not implemented in versions of G2 prior to version 6, so
  # we need to simulate it.
  def api
    @querable_server = TRUE
    begin 
      server_api = @querable_server ? invoke('api') : 4
    rescue XMLRPC::FaultException
      @querable_server = FALSE
      retry
    ensure
      server_api
    end
  end

  # invoke a named method on the chosen server
  def invoke(method)
    server = XMLRPC::Client.new(@host, '/xmlrpc.php')
    server.call('g2.' + method) unless not @methods.include? method
  end
  
  # we have methods for every remote method on the G2 server, be they
  # real of simulated.
  def respond_to?(symbol, include_private = false)
    @methods.include? symbol.to_s
  end

  # implement client methods for each remote method on the G2 server
  def method_missing(method, *arguments)
    method = method.to_s # method is received as a symbol
    invoke method if @methods.include? method
  end
end

# Connect and make sure we support its API
g2 = G2_client.new ARGV[0]

# Show how to check the server version
puts "Server offers API version #{g2.api}"

# Use any method freely
puts "Alphabar"
puts g2.alphabar.to_yaml

puts "\nLatest G2 entries"
puts g2.latest.to_yaml

puts "\nRandom G2 entry"
puts g2.random.to_yaml

puts "\nMost viewed G2 entries"
puts g2.top.to_yaml

puts "\nWord of the day"
puts g2.wotd.to_yaml

puts "\nStats"
puts g2.stats.to_yaml
