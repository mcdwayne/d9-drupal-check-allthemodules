INTRODUCTION
------------

Added login authentication facility to user so that only authenticate user
can access site and This module is intended for use with the Bootstrap theme.
By default the login authentication block place in content region to work 
proper.

Requirements
------------

Bootstrap 3 (https://www.drupal.org/project/bootstrap)
jQuery Update (https://www.drupal.org/project/jquery_update)

Installing
----------

Just enable the module.
You should now see login popup for authentication as well as create new account
and forgot password link.
There are no settings for the module but you can override output as
outlined below.

Overriding output
-----------------

override the theme_bootstrap_login_authenticate_output($vars) function

Similar / related modules
-------------------------
Bootstrap Login Modal Modal
(https://www.drupal.org/project/bootstrap_login_modal)

Adds a login and register link in the nav bar. Opens them in Bootstrap Modal.
This module is intended for use with the Bootstrap theme.
By default the login and register links are added to the navigation region,
but they can be moved on the block admin page.
