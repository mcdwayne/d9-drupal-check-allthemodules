CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

This modules provides the bridge between content and code. It allows developers to use specific entities (like the news-overview page) in their module without knowing the ID of that piece of content.

REQUIREMENTS
------------

This module has no any specific dependency.

INSTALLATION
------------

Installation is very basic download and install the module at admin/modules.

CONFIGURATION
-------------

After enabling the module at admin/modules, configuration can be done by using the correct token in the Pathauto pattern for any content type.

Use cases:

I don't want to save the node id of the homepage in config, instead I tag a node with "Homepage" and System Tags will take care of the rest.
I want to show a list of popular news items in the sidebar on the news overview page, so I tag a node with "News overview" and configure the "System Tags"-condition of that list block to use the "News overview"-System Tag.
DEV-RELEASE The url of an article should start with the alias of the overview page (/articles/my-news-article). This can be done by using the correct token in the Pathauto pattern for articles.

MAINTAINERS
-----------

  * LammensJ - https://www.drupal.org/u/lammensj
