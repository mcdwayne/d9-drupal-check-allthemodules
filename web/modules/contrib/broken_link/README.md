CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration

INTRODUCTION
------------

Module will be needy one to handle many inbound links after Drupal migration.

Broken link module provides following features:
 * Tracking of 404 Page not found request and it hit counts.
 * Can specify the redirection path using regular expression pattern matching.

REQUIREMENTS
------------

This module doesn't have any additional requirements.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
  https://www.drupal.org/documentation/install/modules-themes/modules-8
  for further information.

CONFIGURATION
-------------

 * Manage and configure broken link redirect in Administration » Configuration » System » Broken link redirect:
  - Add/Edit broken link redirect entity with pattern and redirect path.
  - Delete broken link redirect entity.

 * Manage broken link in Administration » Configuration » System » Broken link:
  - Shows tracked broken link and number of hits.
  - Delete tracked broken link.
