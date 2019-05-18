INTRODUCTION
------------

[Bibliography & Citation - Migrate](https://www.drupal.org/project/bibcite_migrate) module provides ability
to migrate your bibliographic data from the [Bibliography Module](https://www.drupal.org/project/biblio)
(aka Drupal Scholar aka Biblio) to [Bibliography & Citation](https://www.drupal.org/project/bibcite) module.
Compatible with 6.x and 7.x versions.

 * For a full description of the module, visit the project page:  
   https://drupal.org/project/bibcite_migrate

 * To submit bug reports and feature suggestions, or to track changes:  
   https://drupal.org/project/issues/bibcite_migrate


REQUIREMENTS
------------

This module requires the following modules:

 * [Bibliography & Citation - Entity](https://drupal.org/project/bibcite)
 * Migrate (Core module)
 * Migrate Drupal (Core module)


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules
   for further information.


CONFIGURATION
-------------

The module has no menu or modifiable settings. There is no configuration.


RECOMMENDED MODULES
-------------------

We are recommend migrate via Drush. There is need next modules to make it. 
 * [Migrate Plus](https://www.drupal.org/project/migrate_plus): Provides enhancements to core migration support
 * [Migrate Upgrade](https://www.drupal.org/project/migrate_upgrade): Drush support for direct upgrades
 * [Migrate Tools](https://www.drupal.org/project/migrate_tools): Tools to assist in running migrations


USAGE
-----

**Back up your database.** This process will change your database values and in case of emergency you may need to revert to a backup.  
Select upgrade method: UI or console. UI is more user-friendly, but console is more flexible.

### Migration via Drush (recommended)

 * Install next modules [the usual way](https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules).
   * [Migrate Plus](https://www.drupal.org/project/migrate_plus): Provides enhancements to core migration support.
   * [Migrate Upgrade](https://www.drupal.org/project/migrate_upgrade): Drush support for direct upgrades.
   * [Migrate Tools](https://www.drupal.org/project/migrate_tools): Tools to assist in running migrations.
 * Create migrations from templates using following command:  
   `drush migrate-upgrade --legacy-db-url=<legace_database> --configure-only`  
   For example:  
   `drush migrate-upgrade --legacy-db-url=mysql://root:root@localhost/drupal7 --configure-only`  
   You can list installed bibcite migrations:  
   `drush ms | grep bibcite`  
   You can remove the bad migration through drush command:  
   `drush config-delete`
 * Start migrations:  
   `drush mim upgrade_bibcite_migrate_reference --execute-dependencies`
 * If something goes wrong you can rollback migration with next command:  
   `drush migrate-rollback --all`

### Migration via UI

 * Install Migrate Drupal UI core module [the usual way](https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules).
 * Start migrate process on **/upgrade** path of your Drupal.  
   See: https://www.drupal.org/docs/8/upgrade/upgrade-using-web-browser for further information.
 * If something goes wrong you can rollback migrated data via console(there's no way in UI that moment),
   and then remove migration group on **/admin/structure/migrate** path of your site.
   After it you can try migrate again.


MAINTAINERS
-----------

Current maintainers:
 * Anton Shubkin (antongp) - https://www.drupal.org/u/antongp
 * adci_contributor - https://www.drupal.org/u/adci_contributor

This project has been sponsored by [ADCI Solutions](http://www.adcisolutions.com/)
