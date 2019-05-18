INTRODUCTION
------------

The DropShark module integrates your Drupal site with the DropShark monitoring,
trending, and alerts service.

CONTENTS
--------

 * Installation
 * Linfo PHP Library
 * Cron Setup
 * Troubleshooting
 * Maintainers

INSTALLATION
------------

Install the module per the normal Drupal module installation procedure. See
https://drupal.org/documentation/install/modules-themes/modules-7 for further
information.

Once installed go to "admin/config/services/dropshark" to register your site
with the DropShark service.

LINFO PHP LIBRARY
-----------------

You will likely want to install the Linfo library to collect additional server
data. See https://github.com/jrgp/linfo.

Installing Linfo can be done by either of the following means:

 * If you use a Composer-based workflow, Linfo will be pull in automatically as
   a dependency.

 * Download and install v3.0.1 into your libraries folder so that the
   standalone_autoload.php file is located at
   libraries/linfo/standalone_autoload.php.

CRON SETUP
----------

The DropShark module will periodically collect data from your site using
Drupal's cron mechanism. You may also supplement this behavior via the following
means:

 * Set up a cron task to call `drush dropshark-collect` or `drush drpshrk`
   as often as you wish.

 * Set up a cron task that makes a HTTP request to your site as often as you
   wish to collect data. The path is "dropshark/collect" and the request needs
   to contain a URL parameter named "key" with the value of the site's DropShark
   site ID. Example http://mysite.com/dropshark/collect?key=
   50e7b4ca-3576-435e-8ead-523ee9d4054e.

Also, you may disable the core cron functionality via the "cron" element in the
`dropshark.settings` configuration to `false`. There is no UI mechanism to set
this, but it may be controlled by importing a config file with the desired value
or setting via drush.

TROUBLESHOOTING
---------------

Please report bugs, issues, and feature requests in the Drupal.org issue queue
at https://www.drupal.org/project/issues/dropshark?categories=All.

Additionally you may contact DropShark support at help@dropshark.io.

MAINTAINERS
-----------

Current maintainers:

 * Will Long (https://www.drupal.org/u/kerasai)
