CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers


INTRODUCTION
------------

The Admin Status module displays messages to users with certain permissions for
important status-related information such as if there are errors or warnings on
the core status page. Custom plugins can be created for additional messages.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/admin_status

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/admin_status


REQUIREMENTS
------------

No special requirements.


RECOMMENDED MODULES
-------------------

 * Timed Messages (https://www.drupal.org/project/timed_messages):
   When enabled and configured, the messages can minimize after a certain time
   so they do not take up so much space at the top of the page.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

 * Configure user permissions in Administration » People » Permissions:

   - Administer admin status messages

     Users in roles with this permission can configure which Admin Status
     messages are shown.

 * Customize the Admin Status settings at Administration » Configuration »
   System » Admin Status.


TROUBLESHOOTING
---------------

 * By default, no Admin Status messages are shown. You must enable messages
   individually on the settings page.


FAQ
---

Q: Why do the Admin Status messages show up after I disable them on the settings
   page?

A: Due to the way the Drupal core messaging system works, the page will show the
   last messages from the message queue. When configuring Admin Status, when you
   disable messages, they will be displayed one last time after you submit the
   form. If you reload the page or go to a new page, then they are no longer
   shown.


MAINTAINERS
-----------

Current maintainers:
 * AmyJune Hineline (volkswagenchick) - https://www.drupal.org/u/volkswagenchick
 * Chris Darke (ChrisDarke) - https://www.drupal.org/u/chrisdarke
 * Darryl Richman (darrylri) - https://www.drupal.org/u/darrylri
 * Kristen Pol (Kristen Pol) - https://www.drupal.org/u/kristen-pol

This project is sponsored by:
 * Hook 42
   Designs, develops, and supports Drupal 7 and Drupal 8 websites and web
   applications for many industries including Enterprise, non-profit, education,
   retail, hospitality, and environmental organizations. Hook 42 specializes in
   SEO-friendly content strategy, responsive design and custom theming, complex
   workflow management, custom module development, multilingual configuration
   and best practices, third-party integrations, and more. Visit
   http://www.hook42.com for more information.
