*********************************************************
-----------------------Drupal to slack-------------------
*********************************************************

Introduction
------------
This module help site builder or developer to add to send notification on slack
channel when content is created or updated. 


Requirements
------------
None


Installation & Use
-------------------
* Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.
* 1. Download project from https://www.drupal.org/drupal_to_slack and unzip
the project.
* 2. Place the project under '/modules/contrib' directory.
* 3. Install it from module installation page.

Configuration
-------------
 * Configure user permissions in Administration » People » Permissions:
 * Find permission "Drupal to Slack."
 * This is permission for access the menu link used to "Drupal to slack".
 * Access link Administration » Configuration » Development » Drupal to slack 
   up setting.
  1. Enter Incoming webhook url. 
  #create webhook_url
   1. Login to slack account using slack credential
   2. Go to yourteam.slack.com/apps/build/custom-integration
   3. Webhooks, then select a channel or user you want to post your messages to 
     (this selection can be overridden later in code) Once done, 
     you’ll see your incoming webhook integration’s configuration page.
     Scroll down and there’ll be a Webhook URL in the format 
     https://hooks.slack.com/services/TXXXXXXXX/BXXXXXXXX/token. 
     Save that URL somewhere, we’ll need it later. You can further change the 
     icon and name of the integration in this page itself, but we’ll do that in code.      
  2. Enter slack channel for notification.
     eg. 'node-edit-form'.
  3. Select Content type to notify on slack
  3. Submit form.
  
Limitation
------------
None 

Features
--------
* Provide update for content creation on slack channel.

CONTACT :
---------

Current maintainers:
  * Sandip Auti (sandipauti) - https://www.drupal.org/u/sandipauti
  * Gmail contact: sandip.auti11@gmail.com
 