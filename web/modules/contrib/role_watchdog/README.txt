
Description
---------------
Role watchdog automatically logs all role changes made through the user profile
or the User List in its own table. A record of these changes is shown in a Role
history tab on each user's page. Role watchdog can optionally monitor one or
more specific roles for changes and notify members of selected roles whenever a
change occurs.

This module might be useful when there are multiple administrators for a site,
and you need auditing or alerting of manual role changes.


Dependencies
---------------
None. Database logging is a Core (optional) module.

Tested compatible with:
* user edit (e.g. http://example.com/user/3/edit)
* user list (e.g. http://example.com/admin/user/user)

Related Modules
---------------
Role Delegation (http://drupal.org/project/role_delegation),
RoleAssign (http://drupal.org/project/roleassign),
Administer Users by Role (http://drupal.org/project/administerusersbyrole)
  modules that enable user access to assign roles to other users where the
  auditing of Role watchdog is a nice fit.

Role Change Notify (http://drupal.org/project/role_change_notify)
  the mirror functionality of Role watchdog, notifying the user when a role is
  added to their account.


Usage
---------------
Role watchdog will automatically start recording all role changes. No further
configuration is necessary for this functionality, the module will do this "out
of the box". A record of these changes is shown in a Role history tab on each
user's page and optionally in the Watchdog log if enabled. Users will need
either "View role history" or "View own role history" access permissions to
view the tab.


Author
---------------
For D8 Port:
 * Gaurav Chauhan (gchauhan) - https://www.drupal.org/u/gchauhan
 * Gaurav Kapoor (gaurav.kapoor) - https://www.drupal.org/u/gauravkapoor


 Supported Organization:
 (OpenSense Labs)https://www.drupal.org/opensense-labs
