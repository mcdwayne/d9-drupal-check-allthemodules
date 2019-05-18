CRYPTOLOG
---------

Cryptolog enhances user privacy by logging ephemeral identifiers in IPv6
notation instead of client IP addresses in Drupal's database tables and syslog.

Cryptolog replaces \Drupal::request()->getClientIp() with an HMAC of the client
IP address using a salt that is stored in memory and regenerated each day.

Because Cryptolog uses the same unique identifier per IP address for a 24-hour
period, site administrators can do statistical analysis of logs such as counting
unique IP addresses per day. In addition, Drupal's flood control mechanism can
function normally.

Note: As long as the salt can still be retrieved, brute force can be used to
generate a rainbow table and reverse engineer the client IP addresses. However,
once the salt has expired and a new salt regenerated, or the web server has been
shutdown or restarted, it should not be feasible to determine client IP
addresses.

INSTALLATION
------------

To avoid storing the salt on disk, you can install the Memcache Drupal module,
the Memcache Storage Drupal module, the Redis Drupal module or the APCu PHP
extension to provide a memory-backed cache. If you do not, Cryptolog will
fallback to storing the salt in your database.

Cryptolog requires that PHP was compiled with IPv6 support enabled.

CREDITS
-------

This module was inspired by the Cryptolog log filter script:
https://github.com/EFForg/cryptolog
