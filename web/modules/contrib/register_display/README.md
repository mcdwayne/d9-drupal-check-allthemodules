CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Register display module allows the user to use the display form as a
register form, which means the user can have a different registration form
based on a combination of user form display and roles.

In some cases, the user might want to have a different registration form for
each role. Rather than starting with customizing a register form using
hook_form_alter the user can use the Register display module.

 * For a full description of the module visit:
   https://www.drupal.org/project/register_display

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/register_display


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Register display module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > People and select "Create
       register page."
    3. In the "Select display" dropdown choose the display: Default or Register.
    4. Enter the Registration page alias; the page url for this role is
       /register-display/administrator.
    5. Enter the page title.
    6. Enter the text for the Submit button text.
    7. Save configuration.
    8. In the Settings tab, the user may now choose to redirect user/register to
       one of custom registration pages.
    9. Select the page and save configuration.


MAINTAINERS
-----------

 * Majdi Alomari (Majdi) - https://www.drupal.org/u/majdi
