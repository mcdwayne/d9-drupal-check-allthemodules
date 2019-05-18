********************************************************************
                     D R U P A L    M O D U L E
********************************************************************
Name: Hotkeys for Save
Author: Andrey Vitushkin <andrey.vitushkin at gmail dot com>
Drupal: 8
********************************************************************

CONTENTS OF THIS FILE
---------------------
 
 * Introduction
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

Do you often edit content or other site data?
Do you get tired of scrolling endless down to the Save button?
If so, this module allows you to use hotkeys Ctrl+S (Win) and Cmd+S (Mac) 
instead of clicking on the Save button.

The term "Save button" should be understood in a general sense.
So, the submit button may have other names, such as:

 'Save block',
 'Continue',
 'Save permissions',
 'Create new account',
 'Finish',
 'Continue & edit',
 'Save and edit',
 'Save and continue',
 'Save and manage fields',
 'Save configuration' and etc.

It should be noted that if these hotkeys are pressed, then browser's 'Save As' 
dialog does not appear.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/hotkeys_for_save

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit:
https://drupal.org/documentation/install/modules-themes/modules-7
for further information.

CONFIGURATION
-------------

Configure user permissions in Administration » People » Permissions:

- Use hotkeys instead of clicking on the Save buttons

To use feature of this module user must have this permission.
Users in 'administrator' role has this permission by default.

It should be noted that if user with this permission press hotkeys Ctrl+S (Win)
or Cmd+S (Mac) then browser's 'Save As' dialog does not appear.
Therefore, do not give this permission to ordinary users.

MAINTAINERS
-----------

Current maintainers:
 
 * Andrey Vitushkin (wombatbuddy) - https://www.drupal.org/u/wombatbuddy
