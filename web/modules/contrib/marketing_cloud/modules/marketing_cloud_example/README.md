MARKETING CLOUD EXAMPLE
=======================


CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Requirements


INTRODUCTION
------------

The Marketing Cloud Example module provides a very simple webform plugin that
sends an SMS text to mobile numbers on form submission.

It expects 2 fields in the webform"

 * mobile_numbers
 * message_text


REQUIREMENTS
------------

 * marketing_cloud
 * marketing_cloud_sms
 * webform
 * webform_ui


INSTALLATION
------------

 * The SalesForce "post a message to a number" API call requires:
   * A short code for an existing text message. Ensure that a message has been
     created at the salesforce end, that you can use or override,
     and enter that shortcodeinto the plugin
     (see /admin/structure/webform/manage/marketing_cloud_example/handlers)
   * The numbers that you want to SMS must already exist in
     SalesForce under the business unit that you define
     in the global Marketing Cloud configuration.


CONFIGURATION
-------------

Please see the
[community documentation pages](https://www.drupal.org/docs/8/modules/marketing-cloud)
for further information on installation and configuration of the Marketing Cloud
and Marketing Cloud SMS modules.
