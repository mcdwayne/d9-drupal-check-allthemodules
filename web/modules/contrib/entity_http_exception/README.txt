CONTENTS OF THIS FILE
----------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------

http://drupal.org/project/entity_http_exception

Entity http exception is a simple Drupal 8 module to allow admin users to setup
http exception(404 or 403) for different type of entity(Node,Taxonomy) view page
on their site. They can also set whether unpublished nodes
will get 404 instead of 403 http exception.
The http exception will only effect anonymous users,
if you have admin users that do
not have permissions to view unpublished nodes, they will still see a
403 Access Denied for these pages.

You can set permission to settings pages for different roles of users.

REQUIREMENTS
------------

 * There are no special requirements outside core.


INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module. See:
     https://drupal.org/documentation/install/modules-themes/modules-8
     for further information.


CONFIGURATION
-------------

* Configure user permissions via
     Administration » People » Permissions
     URL: /admin/people/permissions#module-entity_http_exception


* Settings page is located at: admin/config/system/entity-http-exception

Configuration can be exported in yml file into the configuration directory.

Please blank if you like to skip the settings for different
types of entity of the site.


TROUBLESHOOTING
---------------

 * To submit bug reports and feature suggestions, or to track changes see:
     https://drupal.org/project/issues/entity_http_exception

 * Clear cache to make sure everything working fine.


MAINTAINERS
-----------

Current maintainers:
Takim Islam -           https://www.drupal.org/u/takim
