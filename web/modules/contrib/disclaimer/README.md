CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Disclaimer module watches over users visiting sections of the site and
displays disclaimer when section is entered.

 * For a full description of the module visit:
   https://www.drupal.org/project/disclaimer

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/disclaimer


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Disclaimer module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Block layout. Choose a region
       and select "Place block". Find the "Disclaimer block"
       or "Disclaimer E-mail block" and select
       "Place block" to configure the block.
    3. In the "Redirect" field enter the URL a rejected user is sent to.
       e.g. /content-for-unconfirmed-users.
    4. The "Max-age" field is the time in seconds the user will remain
       confirmed. Set to 0 for no expiry. (86400 seconds = 24 hours)
    5. In the "Challenge" text field, enter the question the user must confirm.
       "Do you agree?" type of question. Agree = User stays on requested page.
       Disagree = User is redirected to Redirect url specified.
    6. Enter the desired text for the Agree and Disagree buttons.
    7. In the "Disclaimer" text field, enter the text displayed to the user on
       a protected page when the user has JS turned off. (No popup with
       challenge is available.)
    8. "Disclaimer E-mail block" aditionaly provides white list of e-mails
       that can pass the validation. This whitelist supports * wildcards.
       One rule per line. For example: *@example.com.
    9. In the vertical tabs, set the visibility for: Content types, Pages, and
       Roles.
    10. Select the Region to display the block. Save block.


MAINTAINERS
-----------

 * Radim Klaska (radimklaska) - https://www.drupal.org/u/radimklaska
 * Jean Valverde (mogtofu33) - https://www.drupal.org/u/mogtofu33

Supporting organization:
 * Morpht - https://www.drupal.org/morpht
