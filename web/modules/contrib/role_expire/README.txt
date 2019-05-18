CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration

INTRODUCTION
------------

Role expire provides expiration to roles attached to users. Some features
available when you enable this module:

 * On the screen where admins create/edit users and assign roles (user/#/edit
   and admin/people/create), those roles can now have expiry dates.

 * A user with sufficient privilege ('administer role expire' or 'administer users')
   can view and edit roles and expiry dates.

 * On the role administration screen (admin/people/permissions/roles/edit), you
   can set a default duration for each role. The expiry duration can be a
   strtotime-compatible string (like "last day of this month").

 * Selecting a role on user_profile_form triggers a textfield (or textfields)
   where admins can enter expiration date/time for the selected role.

 * Defined expiry dates are displayed on the user's profile page, and is
   visible only to owners of the profile or users with proper permissions).

 * Actual role expiration occurs at cron time. Cron automatically removes
   expired roles from affected users.

 * You can configure a default role when each role expires (see Configuration
   section of this README file).

 * Rules module integration: One event and two actions.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.

 * In order to enjoy full potential of role_expire module you need to
   properly configure Cron job on your server:

   https://www.drupal.org/docs/8/cron-automated-tasks

 * IMPORTANT NOTE: Once the module is installed, make sure to configure your
   regional time zone here: admin/config/regional/settings

CONFIGURATION
-------------

 * Configure module's options in Administration » Configuration » System
   » Role Expire:

    - Configure a default role after each role expiration.
