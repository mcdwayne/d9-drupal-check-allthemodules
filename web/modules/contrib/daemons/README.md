# DAEMONS
Provides Daemons module for Drupal 8.

## INTRODUCTION

This module allows to create your own php daemons using event system
 from React PHP library.

## CONFIGURATION

The main configuration page is admin/config/daemons/list where you can
 start\stop daemon. Also, page provides information about each daemon.

## REQUIREMENTS

* **reactphp** React is a low-level library for event-driven programming in PHP. (https://github.com/reactphp/react)
* **pcntl** Process Control php extension. (http://php.net/manual/en/book.pcntl.php)
* **drupal/console-core": "^1.8.0"** Drupal console is required for using drupal console commands.

## INSTALLATION

Place the Daemons module either directly under the /modules directory
at the root of your Drupal installation, or place it under /modules/contrib
directory instead, to group all contributed modules together.

Next, you need to install the module's composer dependencies, there are
multiple ways to do this; you can read more in the https://www.drupal.org/documentation/install/composer-dependencies on d.o.

You can now visit the /admin/modules page on your site and install
the Daemons module.
