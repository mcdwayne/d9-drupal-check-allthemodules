CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Slack module brings all your communication together in one place. It’s
real-time messaging, archiving and search for modern teams, and it has cool
system integrations features.

This module allows you to send messages from a Drupal website to Slack.
It has Rules module integration. You can also use our module API in your
modules.

 * For a full description of the module visit:
   https://www.drupal.org/project/slack

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/slack


REQUIREMENTS
------------

This module requires the following outside of Drupal core:

 * Slack account - https://your-team-domain.slack.com

A webhook integration is required.
    1. Navigate to
       https://your-team-domain.slack.com/apps/manage/custom-integrations.
    2. Select "Incoming Webhooks" and "Add Configuration." Choose a channel
       (or create a new one) to integrate and select " Add Incoming Webhooks
       integration."
    3. Upon saving, the user will be redirected to a page with the Webhook URL.

For more information:
 * https://api.slack.com/custom-integrations
 * https://api.slack.com/incoming-webhooks


RECOMMENDED MODULES
-------------------

To enable the Rules-based Slack integration:

 * Rules - https://www.drupal.org/project/rules

Useful links (about the Rules module):

 * https://www.drupal.org/documentation/modules/rules
 * https://fago.gitbooks.io/rules-docs/content/


INSTALLATION
------------

 * Install the Slack module as you would normally install a contributed Drupal
   module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    3. Navigate to Administration > Configuration > Web Services > Slack >
       Configuration to configure the Slack module.
    3. Enter the Webhook URL that was obtained from
       https://your-team-domain.slack.com/apps/manage/custom-integrations.
    4. Enter the channel name with the # symbol (or @username for a private
       message or a private group name).
    5. Enter the Default user name that you would like to name your Slack bot.
    6. Select the type of image: Emoji, Image, or None (Use default
       integration settings).
    7. Choose if message should be sent with attachment styling.
    8. Save configuration.
    9. To test the messaging system, navigate to Administration > Configuration
       > Web Services > Slack > Send a test message. Enter a message and select
       "Send message." The message should be sent to the selected Slack channel
       or user.


MAINTAINERS
-----------

 * Serge_Konst - https://www.drupal.org/u/serge_konst
 * Evgeny Leonov (hxdef) - https://www.drupal.org/u/hxdef
 * adci_contributor - https://www.drupal.org/u/adci_contributor

Supporting organization:

 * ADCI Solutions - https://www.drupal.org/adci-solutions
