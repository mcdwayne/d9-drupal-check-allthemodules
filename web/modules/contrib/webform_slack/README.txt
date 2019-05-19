Webform: Slack integration
--------------------------

This module provides a webform handler to post a message to a slack channel
when a webform is submitted.

Installation
------------
To use this module, you will need to:

* Install the Slack module, and configure it to post to your slack webhook:
  http://drupal.org/project/slack
* Install the Webform module: http://drupal.org/project/webform
* Edit a webform, visit the Emails/Handlers tab & choose 'Add handler' - you
  will see the slack handler in the available list.

The message supports tokens. HTML is not supported, but lines break
automatically when posted to slack.

Remaining tasks
---------------
* Allow 'default' as a channel selection, which will post to the channel
  set in the Slack configuration screen.
* Implement SlackWebformHandler::getSummary().
* Implement the 'Included message values' to allow better control over the
  [webform-submission:values] token.
* Allow the username & icon to be configurable per form (again, providing 
  default option).
* If the Slack module develops, provide support for advanced formatting
  options: https://api.slack.com/incoming-webhooks#advanced_message_formatting.
* Provide a default message?
