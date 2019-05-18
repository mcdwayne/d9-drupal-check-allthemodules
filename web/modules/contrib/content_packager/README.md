CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers

INTRODUCTION
------------

The Content Packager module gives developers of applications or other offline
deployments a convenient way to extract assets and content from Drupal for
use as app resources.

Rather than trying to parse a REST Export View output or simply
transferring everything out of your public://files directory and hoping for
the best, this module iterates througha REST Export View and produces copies
of your REST text content and associated images.

 * For a full description of this module, please visit:
   https://www.drupal.org/project/content_packager

 * Bug reports, feature suggestions, and other contributons can be made at:
   https://www.drupal.org/project/issues/content_packager

REQUIREMENTS
------------

This module directly depends on several Core modules:
 * File (https://www.drupal.org/docs/8/core/modules/file)
 * Media (https://www.drupal.org/docs/8/core/modules/media)
 * RESTful Web Services (https://www.drupal.org/docs/8/core/modules/rest)
 * Views (https://www.drupal.org/docs/8/core/modules/views)

INSTALLATION
------------

Install as usual, using standard methods.
  
See [Installing Drupal 8 Modules](
https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8)
for complete information.

CONFIGURATION
-------------


TROUBLESHOOTING
---------------

 * If, during the "make zip file" you encounter an error message stating 
   "Fatal error: Maximum execution time..." is displayed or a white screen
   displays.
  
   - Are some files very large?  Files that are large can exceed your 
     max_execution_time PHP setting during the zip process.  Typical
     settings are only 30 seconds.  You will need to determine how to increase
     this setting in your environment or consider zipping the file on your own.
 
MAINTAINERS
-----------

Current maintainer:
 * Weston Wedding (wwedding) - https://www.drupal.org/user/1028158
  
This project has been sponsored by:
 * Sticky Co
   Sticky is a Portland- and Amsterdam-based artist team that makes memorable 
   multimedia. Our work spans technologies and genres, and our clients include
   museums, corporations, transit hubs, festivals, and sports teams.
   More info at www.sticky.tv
