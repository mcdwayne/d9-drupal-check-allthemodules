INTRODUCTION
------------

Original author: Gurpartap Singh <gurpartap@gmail.com>
Current maintainer: Herman van Rink <rink@intifour.nl>

Whois lookup enables Drupal installations to offer whois lookups for their users. The module depends on the phpWhois library available at: http://phpwhois.sf.net/

INSTALLATION
------------

1. Copy the module to sites/SITENAME/modules directory.
2. Download the latest phpWhois package (tested with 4.2.x) from http://sourceforge.net/projects/phpwhois/files/ 
3. Unpack in sites/example.com/libraries/ and rename the directory to just 'phpwhois'
4. Configure the Whois module at admin/settings/whois.
5. Visit the /admin/reports/status page to confirm that the library is available
6. Optionally enable the 'Whois mini form' block

Your module is now setup and ready to be used.

7. For extra security, remove the example* and testsuite.php files from the phpwhois library folder.
