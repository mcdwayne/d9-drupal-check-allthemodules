Ajax Login
----------

INTRODUCTION
------------
  This module is provided an ability to login and register with AJAX.
  This module is provided a block with 3 links (Login, Create new account,
  Reset your password) for the anonymous user.
  After clicking on one of these links user will be able to
  see a requested form in ajax modal window.

INSTALLATION:
-------------
  1. Extract the tar.gz into your 'modules' or directory and copy to modules
     folder.
  2. Go to "Extend" after successfully login into admin.
  3. Enable the module at 'administer >> modules'.
  4. Change a configuration for the module (optional).
  5. Place 'Login and register block' into any region.
  6. Change a configuration for 'Login and register block' (optional).

DEPENDENCIES
------------
  The Ajax Login module has no dependencies, nothing special is required.

CONFIGURATION
-------------
  The configuration page is at admin/config/ajax_login/config,
  where you can configure the Ajax Login module
  and enable challenges for the desired forms.
  You can specify a kind of redirection or choose size parameters for current
  modal window, etc.
  Also there is available special configuration for 'Login and register block'
  (admin/structure/block/manage/loginandregisterblock).
  You can enable needed links and specify output type.

UNINSTALLATION
--------------
  1. Disable the module from 'administer >> modules'.
  2. Uninstall the module

MAINTAINERS
-----------
  Current maintainers: https://www.drupal.org/u/sergdidenko
