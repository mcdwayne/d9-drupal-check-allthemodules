Module: EdgeCast
Author: Mike Carter <http://drupal.org/user/13164>
Author: Guilherme Lopes <http://drupal.org/u/guilopes>


Description
===========
The Edgecast module connects your Drupal site to the CDN so that when you edit content the CDN knows to update it's cache quicker than the standard expiry time you've defined (usually hours or days).
When used in conjunction with the Cache Expiration module <http://drupal.org/project/expire> you'll get support for Nodes, User profiles, comments and more. When used standalone it will purge the cache just for node updates.


Requirements
============
* Edgecast account
* Purge and Purge Queuer URL Modules


Installation
============
* Copy the 'edgecast' module directory in to your Drupal modules directory as usual.

* Configure your Edgecast account details at /admin/config/development/edgecast/api
 - Customer ID - This can be found at the top right of every page at https://my.edgecast.com
 - Edgecast Token - This can be found under the 'Web Service REST API Token' area in 'My Settings' <https://my.edgecast.com/settings/>
 - Default Path - The fully qualified domain name of your Edge Cname

* Configure Purge module on admin/config/development/performance/purge and add Edge Cast Purge

* On queue click on configure in URLs queuer and check 'Queue paths instead of URLs' after that start to navigare on the site and edit some content, it should go to edgecast queue.

* PS: There're a limit of 50 items cleared per time

Usage
=====
* Upon updating individual nodes purge requests will be automatically send to Edgecast.

Advanced Settings
=================
