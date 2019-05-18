
Contents of this file
---------------------

 * Overview
 * Installation
 * Customization
 * Features

Overview
--------

Timeout Notification is a module utilizing Drupal's session lifetime settings
found in the services.yml file. This will provide the users a simple notification
of an upcoming session expiration, allowing them to renew their current session
without losing any critical data due to unknown loss of session.


Installation
-----------

1. Place the timeout-notification directory in your modules directory.
2. Enable the Timeout Notification module at admin/modules.

Customization
-------------
For control of Drupal's session variables, they can be accessed through sites->default->services.yml.

  * `gc_maxlifetime` - is used to  set the session lifetime (in seconds).  i.e. the time from the user's last
      visit to the active session may be deleted by the session garbage collector. When a session is deleted,
      authenticated users are logged out,and the contents of the user's $_SESSION variable is discarded.

      By default `gc_maxlifetime` is set to 200000 seconds.

Configure Timeout Notification settings.

  * settings can be altered at `admin/config/timeout_notification` for project specific customization.

      i.e seconds in advance to notify users of upcoming session expiration. By default, this is set to 60 seconds.


Features
--------

The module was designed to allow exporting of all the admin configuration options via Drupal 8 Configuration Management.
