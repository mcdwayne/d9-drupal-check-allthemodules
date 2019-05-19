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
The Social channel feed module allows user to fetch feeds/data from their different social channels. User can fetch 
feeds from Facebook, Twitter, LinkedIn, Youtube, Instagram. Feeds data can display in page or in block configuration. User can set separate block for each social media channel.

* For a full description of the module, visit the project page:
   https://www.drupal.org/project/socialmediafeed

REQUIREMENTS
-----------
Drupal 8

Installation
------------
* Install as you would normally install a contributed Drupal 8 module. See:
  https://www.drupal.org/node/1897420
  https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
  for further information.

CONFIGURATION
------------- 
* Go to admin >> config >> Social Media Feed >> Click on Social Media Feed Configure link. (SITE_URL/admin/config/social-media-feed/config).
* Default Global tab will open, select the number of feed per page, cache settings.
* Go to Each tab settings and provide the related values. 
* Click on check box on each tab settings to enable the feed.
* Save the each tab settings.
* Visit the url SITE_URL/social-media-feed for feed page
* Setup block for the feed
 * Go to admin >> Structure >> Block >> Click on Place block button (Popup window will open).
 * Filter the Social Media Feed Block and click on the Place Block Button
 * Click on the Save block button.

TROUBLESHOOTING
---------------
* If the feed does not display on the page, check the following:

   - Enable/Disable the cache from the Global setting tab.
   - Clear the Drupal cache once.
   - Check the Social media Authentication.

MAINTAINERS
-----------
Current maintainers:
* Govind  Maloo(govindmaloo) - (https://www.drupal.org/u/govindmaloo)
* Hemant Sharma(myhemant)    - (https://www.drupal.org/u/myhemant)