CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Usage

INTRODUCTION
------------

Current Maintainers:

 * alayham https://www.drupal.org/user/34525

Basic perimeter defence for a Drupal site. This module bans the IPs who send
suspicious requests to the site. The concept is: if you have no business here,
go away.

Use the perimeter module if you get a lot of requests to 'wp-admin' or to
.aspx urls on a linux server, or other similar requests.

The module is optimized for performance and designed to be activated when
a Drupal site is targeted by hackers or bots.

INSTALLATION
------------

Drupal Perimeter Defence can be installed via the standard Drupal installation
process (http://drupal.org/documentation/install/modules-themes/modules-8).

USAGE
-----

Just enable the module, and check your site logs after a while.
Use the core's ban module to manage banned IPs.

Note: Before testing this module from your own IP, make sure you can delete
your IP from the ban_ip table in your Drupal site's database.