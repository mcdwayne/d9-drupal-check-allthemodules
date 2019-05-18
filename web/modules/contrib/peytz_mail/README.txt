README FOR PEYTZ MAIL INTEGRATION MODULE FOR DRUPAL 8.x
=======================================================

INTRODUCTION
------------
This module supplies a way to sign up to newsletter lists on a Peytz Mail
account. It also comes with helpers for contacting the Peytz Mail API functions.
Other modules may include additional signup form fields using a hook.

* For a full description of the module, visit the project page:
   https://drupal.org/project/peytz_mail

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/peytz_mail


REQUIREMENTS
------------
No special requirements.


INSTALLATION
------------
1. Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.
2. Set up module permissions.
3. Go to /admin/config/peytz_mail/settings to configure the module:
     - Select the 'Service URL protocol'. Most likely you will use the default.
     - Enter the path to your Peytz Mail account in 'Service URL'.
     - Enter your Peytz Mail API key.
     - Save.
4. There is now a block available which can be configured to allow signing up
   to the selected newsletter lists.


MAINTAINERS
-----------
Current maintainers:
 * Yonas Haile (zyosarian) - https://www.drupal.org/u/zyosarian
 * Achton Smidt Winther (achton) - https://www.drupal.org/u/achton

This project has been sponsored by:
 * Peytz & Co (http://peytz.dk/english)
   Peytz & Co is a full service web agency established in 2002. Peytz & Co is
   owned by key employees and brings together 100+ creative, analytical and
   technical minds.
