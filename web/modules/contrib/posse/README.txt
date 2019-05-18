CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers


 INTRODUCTION
 ------------

 The POSSE module aims to solve the issue of how to easily setup a method of
 syndicating content across various destinations by providing a central and
 standardized way for integration. The aim is to have something akin to the
 DBTNG where we abstract individual platform apis out of the sphere so
 we can simplify interacting with them from a Content publishing standpoint.

 The module itself is just providing a plugin system for implementing various
 platforms and we aim to have several major platforms supported soon.

 Currently supported platforms:

  - Webmentions.io - You will need to create an account on Webmentions.io, atm
    this was the lowest barrier of entry for handling pingback / webmentions that
    I found.  Later on we may add support for self-hosted pingback support.

  * For a full description of the module, visit the project page:
    https://drupal.org/project/posse

  * To submit bug reports and feature suggestions, or to track changes:
    https://drupal.org/project/issues/posse

REQUIREMENTS
------------

Currently this module has no external requirements, but that may change overtime.

INSTALLATION
------------

 * Currently the easiest way to install this module is to use composer:
   composer require durpal/posse

CONFIGURATION
-------------

Configuration for this module and the plugins that are defined to work with it
can be found at /admin/config/services/posse.  Here you can configure the various
APIs and authenticate the Third-Party sites you wish to syndicate to.

Additional configuration on how you want your content to display is found on the
Entity Display Settings page.

MAINTAINERS
-----------

Current maintainers:
 * Chris McIntosh (cmcintosh) - https://drupal.org/user/54136

This project has been sponsored by:
 * Wembassy
   Specialized in consulting and planning of Drupal powered sites, Wembassy
   offers installation, development, theming, customization, and support
   to get you started. Visit https://www.wembassy.com for more information.
