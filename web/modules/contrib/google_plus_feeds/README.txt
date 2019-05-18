CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Implementation
 * Installation and configuration

INTRODUCTION
------------
* This module fetch the Google plus feeds from configured Google account id.
* It displays the feeds in a block,so that it can be configurable to any region.

FEATURES
--------
This module displays latest Google plus feeds of the configured account.

IMPLEMENTATION
--------------
Basically this module is displayes the feeds with the help of Google feed API.
User need to enable Google API and configured it into site's configuration page.

After adding this configuration admin can configure the google plus feed block
to the perticular reagion of the site. The block will displays the feeds.

INSTALLATION AND CONFIGURATION
------------------------------
1 - Download the module and copy it into your contributed modules folder:
[for example, your_drupal_path/modules] and enable it
from the extend page.

2 - Configuration:
After successful installation, browse to the Google account configuration page,
and after add the details. After adding the details browse to the Block
configuration page, configure the Goole plus feed block into a region.
The path to configure Google account details would be:
admin/config/googleplus and 
breadcrumb: Home » Administration » Structure » Blocks to configure block.
Click on "Place block" button and select search block and configure it.
Once configuration done it can be dragged & dropped into any other region.

========================
Google Plus Feeds module
========================

DESCRIPTION
------------
Google Plus Feeds fetch feeds from Google Plus account and display it in block
on the site.

REQUIREMENTS
------------
Drupal 8.x

INSTALLATION
------------
1.  Place Google Plus Feeds module into your modules directory.
    This is normally the "sites/modules/custom" directory.

2.  Go to admin/modules. Enable the modules.

3.  Configure google account details in admin/config/googleplus

4.  Go to admin/structure/block to configure "Google Plus Feeds"


FEATURES
--------
The detailed manual for developers is in development.
Visit the project page on Drupal.org:
https://www.drupal.org/project/google_plus_feeds

-------------------------------------------------------------------------------

HOW TO SETUP GOOGLE ACCOUNT ID AND API KEY
------------------------------------------
1.  Visit https://console.developers.google.com/project URL.
2.  Create a project and all the site details.
3.  Click on "Enable an API" then click on Google+ API link under "Social APIs".
4.  Click on "Enable API".
5.  Add credentials from the left hand side menu.
6.  Click on "Credentials" and then on "Create new Key".
7.  Select "Server Key" from the pop-up appears.
8.  Add website URL or website IP address and click on "Create". Copy API key
    and paste it in configuration text box.
