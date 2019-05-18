CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Important notes
 * Maintainers

INTRODUCTION
------------

Multilingual Login Redirect module allows you to redirect a user to a specific
url or node number when he logs in depending on the actual language of the
website and the user role.
The module allows to put a general role for each language and then adding
exceptions for each role.

REQUIREMENTS
------------

No other modules required.

INSTALLATION
------------

Download or clone the project from the official module page
https://www.drupal.org/project/multilingual_login_redirect
or install it via composer. After this just enable the module in Drupal.

CONFIGURATION
------------

Config page: /admin/config/multilingual-login-redirect

Go to the config page, set the rules you need for each language and add your
exceptions for roles if needed.
After that you can save the configuration.

Allowed rule values are:
 * relative paths, for example /it/blog
 * absolute paths, for example http://mysite.com/it/blog
 * node number following this format: node:[node_id], for example node:20

IMPORTANT NOTES
------------

 * If a user has multiple roles the module will apply the rule using the role
   with the highest weight (you can set the role weight under the ‘People’
   section /admin/people/roles and the highest role is the one at the bottom of
   the list).
   So if for example in the list you have authenticated user at the top and
   administrator at the bottom, the module will use the administrator exception
   you have set.

MAINTAINERS
-----------

Current maintainers:
 * Alessandro Scolozzi (AlessandroSC) - https://www.drupal.org/user/3449983
