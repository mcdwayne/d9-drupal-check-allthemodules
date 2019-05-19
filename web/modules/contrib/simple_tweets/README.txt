CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------
This module adds integration Twitter-Post-Fetcher in Drupal.
Twitter-Post-Fetcher get your tweets and displaying on your website using
JavaScript,without using Twitter 1.1 API.

REQUIREMENTS
------------
Jquery Update

INSTALLATION
------------
* Download and enable the module.
 (See:https://drupal.org/documentation/install/modules-themes/modules-8
 for further information.)
* Download the Twitter-Post-Fetcher from
 https://github.com/jasonmayes/Twitter-Post-Fetcher
 Direct download link:
 https://github.com/jasonmayes/Twitter-Post-Fetcher/archive/master.zip
* Extract the downloaded zip to a folder in your Simple Tweets module folder.
* rename the Twitter-Post-Fetcher-master folder to 'Twitter-Post-Fetcher'.
* Check the path to the Twitter-Post-Fetcher javascript. It should be
DOCROOT/modules/simple_tweets/includes/Twitter-Post-Fetcher/js/twitterFetcher_min.js
[adjust this path if your libraries folder is in a different location]

CONFIGURATION
-------------
For module configuration please go to:
<yourdomain>/admin/config/services/simple_tweets

You can get your widget ID in configuration page link, for example:
twitter.com/settings/widgets/<your widget ID>/edit

MAINTAINERS
-----------
Current maintainers:
 * Denis Pushkarev https://www.drupal.org/user/3370000
