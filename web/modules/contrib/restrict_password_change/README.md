CONTENTS OF THIS FILE
---------------------
  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Maintainers

INTRODUCTION
------------
Sometimes we wanted to be able to allow some users to add other users, 
but not change any user's password.

When the user profile form is loaded it checks to see if the current user 
has the proper permission or if they are editing their own account,
otherwise it removes the password change option.
There would be no point restricting the ability to change a user's password 
if they can still change the user's e-mail address,
this option is removed as well.
This also removes the option to delete a user.

This module adds few new permissions:
  * change other users password
  * change own password
  * reset password by request link
  * change other users username
  * change other users email
  * delete other users
  * block other users

 For a full description of the module, visit the project page:
   https://www.drupal.org/project/restrict_password_change

 To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/restrict_password_change

REQUIREMENTS
------------
The module has no needs to install new modules.
Just needs configure user administrators privileges.

INSTALLATION
------------
* Put the module in your Drupal modules directory and enable it in 
  admin/modules. 
* Go to admin/people/permissions and grant permission to any roles that need.

CONFIGURATION
-------------
  * Configure user permissions in Administration » People » Permissions:
    - Use the administration pages and help (System module)
    - Allow 'Administer users'
    - Enable some the new permissions listed above.

MAINTAINERS
-----------
  * Currently maintained by 
  [Heissen Lopez (heilop)](https://www.drupal.org/u/heilop).
  * The initial development was by 
  [James Glasgow (jrglasgow)](https://www.drupal.org/u/jrglasgow).
