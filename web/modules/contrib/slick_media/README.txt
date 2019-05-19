ABOUT

This module is deprecated for main Slick module as Blazy 2.x now depends on core
Media. This means Slick doesn't need to have a separate Slick Media anymore.

The main reason for separation was a dependency on contrib Media Entity.
Since Media Entity was replaced by core Media, and Blazy depends on it,
this module becomes redundant.

Slick Media is now just a plugin on the main Slick module.

IMPORTANT!
Do not manually uninstall this module, or you will lose your formatter.
Instead follow the steps below.

Steps:
1. Have a backup routine.

2. Test it out against a DEV environment before production.

3. Download Blazy 2.x and Slick 2.x.

4. Visit "/update.php", or run "drush updb".
   Run the provided hook_update().
   The update will do two things automatically:
   * migrate this module formatter into main Slick module,
   * uninstall this module for you.

5. Visit /admin/modules
   Verify that this module is uninstalled after the update is run.

6. Only delete this Slick Media module once you verify than it is uninstalled,
   and your Slick is working as normal, only now using the main Slick formatter.


Please report any issue so that we can address them. Thanks!
