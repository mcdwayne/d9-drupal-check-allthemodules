Lightbox Campaigns
==================

CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Use
 * Maintainers
 * License

INTRODUCTION
------------

Lightbox Campaigns enables the creation of "campaigns" that can be configured to
display full page content to users based roles, content types, and/or paths.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/lightbox_campaigns
   
 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/lightbox_campaigns

REQUIREMENTS
------------

This module requires the following libraries:

 * Featherlight.js
   https://noelboss.github.io/featherlight/

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.
   
 * Install the Featherlight.js library:
 
   - Download the latest release from 
     https://github.com/noelboss/featherlight/releases.
     
   - Unpack the library files.
   
   - Place the files in your site's /libraries folder such that the location is
     /libraries/featherlight.

CONFIGURATION
-------------

 * Configure user permissions in Administration » People » Permissions:

   - Add lightbox campaigns
   
     Enables a user to add new lightbox campaigns.
   
   - Administer lightbox campaigns
   
     Users need this permission to access the campaigns list and generally 
     administer news/existing campaigns.
     
   - Delete lightbox campaigns
   
     Enables a user to delete *any* existing lightbox campaigns.

   - Edit lightbox campaigns
   
     Enables a user to edit *any* existing lightbox campaigns.
     
USE
---

Lightbox campaign entities can be added from Content » Lightbox campaigns.

Every campaign entities has the following fields by default:

 * Campaign name (string)
 
   The name used in administrative pages to identify a campaign.
 
 * Enabled (boolean)
 
   Whether or not the campaign is enabled. Campaigns that are not enabled will 
   not display to users under any circumstances.
   
 * Lightbox content (formatted text)
 
   The content to appear in the lightbox displayed to users.
   
 * Reset timer (select)
 
   The amount of time to wait before displaying the lightbox to a user who has
   already seen it.
   
 * Start date/time (datetime)
 
   Earliest date and time that the lightbox should begin displaying to users.
   
 * End date/time (datetime)
 
   Latest date and time that the lightbox should display to users.
   
 * Visibility
 
   The following rules can be used to fine-grain the circumstances under which
   the campaigns's lightbox will be displayed to users. Any number of rules can
   be combined and *all* rules must pass for content to be displayed.
 
   - Content Types
   
     If any content types are selected, the lightbox will only display on pages 
     of the content types selected.
     
     If no content types are selected, the lightbox will display on pages of 
     *any* content type.
     
   - Roles
      
     If any user roles are selected, the lightbox will only to users belonging
     to the selected roles.
     
     If no user roles are selected, the lightbox will display to *all* users.
      
   - Paths
   
     If paths are listed and the "Show only for the specified paths" option is
     selected, the lightbox will only appear on the listed paths.
     
     If the "Hide for the specified paths" option is selected, the lightbox will 
     display on any path *expect* the listed paths.
     
     If no paths are listed, the lightbox will display on *any* path.
     
      
MAINTAINERS
-----------

Current maintainers:
 * Christopher Charbonneau Wells (wells) - https://www.drupal.org/u/wells

This project is sponsored by:
 * [Cascade Public Media](https://www.drupal.org/cascade-public-media) for 
 [KCTS9.org](https://kcts9.org/) and [Crosscut.com](https://crosscut.com/).
 
LICENSE
-------

All code in this repository is licensed 
[GPLv2](http://www.gnu.org/licenses/gpl-2.0.html). A LICENSE file is not 
included in this repository per Drupal's module packaging specifications.

See [Licensing on Drupal.org](https://www.drupal.org/about/licensing).
