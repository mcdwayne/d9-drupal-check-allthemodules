CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The copyscape module integrates Copyscape API and checks the originality of
content published. In order to use this project, you will have to purchase a
Copyscape subscription. They don't expose their API for free accounts. You can,
however, test an URL from their website.

This project aims to help large content sites with multiple editors to ensure
the published nodes are not partially/entirely copied from other sources.

 * For a full description of the module visit:
   https://www.drupal.org/project/copyscape

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/copyscape


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

Full functionality of this module requires an account on the Copyscape API
service.

* Copyscpe - http://www.copyscape.com


INSTALLATION
------------

Install the copyscape module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Copyscape > API to configure
       the copyscape module.
    3. Create an account on copyscape API service and insert the account details
       in copyscape module settings.
    4. Navigate to Administration > Configuration > Copyscape > Content and
       check the desired content type fields to be added to the copyright
       checked list. The user can select as many content types/fields as they
       like, but the available fields are limited to long texts.
    5. Now, every node added/edited will be tested against Copyscape API
       functions, unless the user can bypass the check. The user with uid=1
       bypasses this check. Additional roles can be added to the bypass list
       from project's user configuration.


MAINTAINERS
-----------

 * Adrian ABABEI (web247) - https://www.drupal.org/u/web247

Project maintained and supported by:

 * Optasy - https://www.drupal.org/optasy-0
 * ALLWEB247 - https://www.drupal.org/allweb247
 * Optasy - https://www.drupal.org/optasy
