# TWITTER SYNC

## INTRODUCTION

Module created to sync your account tweets and insert it on Drupal CMS.

## REQUIREMENTS

No requirements.

## INSTALLATION

Install as usual, see
 <https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules>
 for more information.

## CONFIGURATION

 - Create an app on <https://developer.twitter.com>
 - Generate your consumer and token keys - make sure it they are Read and Write
 - Visit /admin/config/system/twitter_sync
 - Input your keys in their respective form fields
 - Go to /admin/structure/block
 - Place the block 'Twitter Sync block' in the region you want
 - Run cron.php

## HOW TO USE

This module will automatically get the latest 3 tweets and display as a block.
You just need to ensure cron.php runs periodically so your latest tweets are
read and updated in the site.

## MAINTAINERS

Current maintainers:

* Yago Elias - <https://www.drupal.org/u/yago-elias>
* Leonardo Paccanaro - <https://www.drupal.org/user/1901878>
