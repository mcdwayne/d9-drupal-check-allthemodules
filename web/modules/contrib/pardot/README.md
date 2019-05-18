CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Key Features
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Pardot module adds Pardot web analytics integration to Drupal.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/pardot

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/pardot

About Pardot
Pardot offers a software-as-a-service marketing automation application that
allows marketing and sales departments to create, deploy, and manage online
marketing campaigns that increase revenue and maximize efficiency. Pardot
features certified CRM integrations with salesforce.com, NetSuite, Microsoft
Dynamics CRM, and SugarCRM, empowering marketers with lead nurturing, lead
scoring, and ROI reporting to generate and qualify sales leads, shorten sales
cycles, and demonstrate marketing accountability.

Note: A standard account or higher is required to use this module.


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


KEY FEATURES
------------

 * Default campaign for web activity tracking.
 * Conditional path and user role web activity tracking.
 * Path based individual campaign tracking.
 * Path based scoring.
 * Core contact form integration.


INSTALLATION
------------

 * Install the Pardot module as you would normally install a contributed Drupal
   module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Modules and enable the module.
    2. Navigate to Administration > Configuration > Web Services > Pardot to
       create and edit Pardot settings.
    3. Enter the Pardot Account ID and Default Pardot Campaign ID.
    4. Add tracking to specific pages. Specify pages by using their paths.
       Enter one path per line. The '*' character is a wildcard. An example
       path is /user/* for every user page. <front> is the front page.
    5. From the Roles vertical tab the user can add tracking for specific
       roles.
    6. The user can also add Campaigns, Scores, and Contact Form Mappings.
    7. Save configuration.


MAINTAINERS
-----------

Supporting organizations:

Module maintainership
 * Mediacurrent - https://www.drupal.org/mediacurrent
 * Elevated Third - https://www.drupal.org/elevated-third

Initial implementation
 * APQC - https://www.drupal.org/apqc
