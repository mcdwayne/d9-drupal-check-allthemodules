http:BL (README.txt)
---------------------------------------------------------

/**
 * Implements Project Honey Pot's http:BL, for Drupal. It provides IP-based
 * blacklisting through http:BL and can place honeypot links on page bottoms.
 *
 * Version 8.x
 *
 * Drupal 8 port + Admin UI.
 * Drupal 7 port
 * Drupal 6.2 fork
 *
 * @author Bryan Lewellen (bryrock)
 * @link http://drupal.org/u/bryrock
 * @link http://drupal.org/project/httpbl
 * @link http://httpbl.org/
 *
 * Additional D6 code support by David Norman (deekayen)
 *
 ***
 * Original Drupal 5 & 6 modules
 *
 * @author Mark Janssen (praseodym) 
 * @link https://www.drupal.org/u/praseodym
 */

New in Drupal 8!

 * Blocked IPs are now stored as "Host" entities
 * Hosts can be administratively managed (add/edit/delete) without direct database access
 * "Auto-ban" option works with core Ban module, allows managing banned IPs with Host expiry
 * http:BL is a middleware service
 * Drush commands: If you goof up and get yourself blacklisted and banned, just "drush sos --stop"!

Key Features (historical):

 * Project Honeypot Blacklist lookups for visitor IPs
 * Blocking of current and future requests from blacklisted IPs
 * Local database storage, decreases DNS lookups on repeated visit attempts
 * Honeypot link placement on refused responses and optionally in site-wide page bottoms
 * Stored White-listing for safe IPs, and Session white-listed for grey ones that can prove their human
 * Greylisting: Intermediate blocking of medium-threat IPs, grants user access after passing a simple challenge
 * Option to check/block only on Comment submissions (unpublishes comments from bad IPs and bans them from future visits)
 * Configurable log volume (Quiet, Minimal or Verbose)
 * Configurable expiration of stored visits (now host entities)
 * Basic statistics on the number of blocked visits
 * Can be used for Honeypot link placement only (no blocking)

Http:BL stops reputed email harvesters, dictionary attackers, comment spammers and other disreputable, nuisance traffic from visiting your site by using the centralized DNS blacklist at Project Honey Pot (http://www.projecthoneypot.org/).

Http:BL requires a free Project Honey Pot membership. Http:BL provides fast and efficient blacklist lookups and blocks first-time malicious visitors.  IPs of previously blocked visitors are stored locally and kept from returning for admin configurable periods of time, eliminating further remote DNS lookups during that time.  Blacklisted IPs can (optionally) be automatically banned via Drupal's "ban_ip" table.  Likewise, "safe" or white-listed IPs are also stored locally for configurable periods of time.

Optional Verbose logging (not recommended for production) is useful for testing and gaining trust of this module and the HttpBL service at projecthoneypot.org.

Http:BL formerly included Views based reports for seeing blocked and safe IPs.  These have been replaced with (optional) Views based "better admin" to enhance the default entity management forms, and include bulk operation actions.

Http:BL can be configured to lookup IPs only for comment submissions.  Comments are forced unpublished but saved for comment review. Original comment content is saved, along with information to reviewer explaining why the comment was blocked, and includes hints for better configuration options.

Http:BL can place hidden Honeypot links in page bottoms.  These make it possible for you to participate and "give back" to Project Honey Pot by identiying fresh nuisance IPs that may not yet be ranked as threats in Project Honeypot profiles.  They find these links irresistible, and by "clicking" expose their existance and reports them for futher profiling at projecthoneypot.org.  

  
 *
 ** Some Notes About Testing and How This Works **
 *
 
Past versions of this module relied heavily on the logs and reports if you wanted evidence of what it does, especially if you didn't have direct access to your database, allowing you to see how quickly it starts catching nasty IP addresses.  This is no longer the case now that captured traffic is manageble with an admin UI.  Nonetheless, briefly monitoring the verbose logging will give you some idea of how it does what it does.

In a nutshell, when a visitor arrives at your site, their IP is checked locally to see if they've visited before and how they were  ranked.  If found they immediately get the "appropiate" response.  If not, the IP is looked up at Project Honey Pot for a threat score.  If there is no score or the score is below your configured threshold, they are locally white-listed.  If the threat score is above your blacklist threshold, they are blacklisted.  If somewhere in between safe and blacklisted, they are grey-listed and challenged to take a simple test.  Failing that test converts them to blacklisted.  Success temporarily white-lists them for a limited, current session basis.

Whether found locally or found remotely (then stored locally), the "appropriate" response is the default 200 (OK) response, for a safe IP, and they get to where they were going.  A blacklisted IP immediately received a 403 (FORBIDDEN) response, and will continue to do so until their stored status expires.  A grey-listed IP will continue to receive a 428 (PRECONDITION REQUIRED) response until converted to a blacklisted or session-based white-listed IP, or they leave.  When they fail the challenge, they receive a 412 (PRECONDITION FAILED) response, immediately, and thereafter the forbidden response, until their stored status expires.  In the event they are session-based white-listed, they remain stored as a grey-listed IP, and when their session expires they will continue to be challenged until their status expires.

Expirations are handled by cron.  Once any IP or Host entity -- safe, grey or blacklisted -- expires, the process starts over again if and when they return.

Admin testing and drush commands
--------------------------------

As for testing the admin UI, there are some drush commands that will allow you to quickly create batches of bogus host entities, so you don't have to wait for or practice on real IPs.

To find these commands: "drush help | grep httpbl"

As soon as you enable the module, assuming you use one of the two options to store the Host entities, your IP will show up as a host entity.  Should you get carried away and blacklist yourself, you will be blacklisted!  In other words, locked out.

So, before you test the admin UI, make sure you know your own IP and have drush access, especially if you are not testing a local or desktop instance of your site.

drush sos - without any options this will white-list and un-ban the localhost (127.0.0.1).
drush sos [your IP] - will white-list and un-ban your IP.

If you panic...

drush sos --stop - will stop all page request checking so you can go in via the UI and fix your IP.  The quickest way is to delete it, because -- assuming you don't actually have a high threat score at Project Honey Pot -- you'll be automatically white-listed again as soon as you re-start page request checking.  And that you can do either through admin config page, or...

drush sos --start

Determining the Thresholds
--------------------------------

Every site has different requirements and traffic styles, so you may need to fine-tune and see what works best for your site.

While it may be tempting to set the Grey-list threshold as low as possible (1), if you have a heavily trafficked site with lots of valuable, anonymous users, it may be necessary for you to tolerate some higher risk (in the 3-5 range, for instance).  Otherwise, you may find you are blocking good-intentioned users who, unfortunately, have slightly compromised computers/IP addresses.  The worst case scenario is in an environment where someone in management or another VIP needs free access and has a lowsy IP!

On the other hand, the blacklisting threshold defaults to 50 and, hopefully, you won't need to change that.  Any IP at Project Honey Pot that has a threat score of 50 or higher is nearly guaranteed to be up to no good!  Nonetheless, if you must go higher, you can in increments of 5, but once you get anywhere near 100, you might as well stop using this module, because you will just be inviting bad traffic to come in and stay, anyway.


Determining the Expiry
--------------------------------

First, nothing is "permanent" and this module does not do white,grey or blacklisting on a case-by-case (IP-by-IP) basis.  There are, however, different expiry periods for safe, grey and blacklisting, and they ALL eventually expire.  There is no "permanent" white-listing, grey-listing or blacklisting.

If you are in that environment with the boss/VIP who has a dirty IP, you may need to white-list them through the admin UI, and then set the safe expiry as high as it goes (4 weeks), so you refresh them about once a month.

Otherwise, while it may seem counter-intuitive, shorter expiry for whitelisting is best.  Truly safe IPs will always refresh themselves after expiration, as long as that visitor keeps returning.

Another reason not to set the white-list expiry too high is there are periodic occassions where Project Honey Pot may be unavailable for short periods of time for maintenance or due to other internet issues, leaving your site briefly unprotected.  During that time, any IP getting in the door will be white-listed, so just in case it isn't deserved, it's best if they expire as soon as they can, so they can be more appropriately ranked if they return while all services are up and running.

Feel free to use much longer expiration for blacklisted IPs (up to 3 years), because they often tend to only get worse (more threatening) over time, and there's no need to let them on your site.

Grey-listing expiration options range from 12 hours to 4 weeks.  As with determining the threshold, do what's best for your site.

I hope you find this module and the http:BL service at Project Honey Pot to be useful!

Bryrock

 