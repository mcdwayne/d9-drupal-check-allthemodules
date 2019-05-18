CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Usage
 * Testing
 * Maintainers
 
INTRODUCTION
------------

The Client Config Care module was introduced to speed up live site deployments by not overwriting configuration changes made by editors with config editing permissions, e.g. changes on blocks or menus.

The Client Config Care module extends Drupal with functionality for tracking configuration changes made via the administrative
interface in the web browser. On live instances these changes are mainly made by clients, meaning users of
the websites, such as editors. Website maintainers usually make config changes in their local development environment
and export configuration to version it via a version control system such as Git.

Without Client Config Care installed, if your clients made config changes on a live instance, you would get unrelated conflicts if you wanted to deploy configuration from your development environment to the live system.  

For example if you are configuring a new module, but the editor has configured blocks and menus, then Client Config Care will
have tracked changes on block and menu configuration, which will allow a config import via the 
[Drush](https://www.drush.org/) command line tool, without overwriting any client configuration.

REQUIREMENTS
------------
This module requires the [Config Filter](https://www.drupal.org/project/config_filter) module and PHP at least version
7.1. Client Config Care is meant to be used with [Drush](https://www.drush.org/) for importing configuration to your (live) Drupal instance.

INSTALLATION
------------
 * Install the Client Config Care module as you would normally install a
   contributed Drupal module. Visit [https://www.drupal.org/node/1897420](https://www.drupal.org/node/1897420) for
   further information.

CONFIGURATION
-------------
* You can edit your `settings.local.php` for disabling Client Config Care's functionality. Client Config Care will not
  create any `Config blocker entities` or block config from being overwritten. This setting is recommended on local
  development environments. Just write the following lines into your `settings.local.php` file to deactivate Client
  Config Care:
  ```
  $settings['client_config_care'] = [
    'deactivated' => TRUE,
  ];
  ```

USAGE
-----
* Visit `/admin/structure/config_blocker_entity` in your sites backend to get an overview over all config blocker
  entities and optionally delete any of them.
* Save any config such as site name at `/admin/config/system/site-information`. If you have not deactivated Client
  Config Care via the `settings.php` file, you will see a `config blocker entity` in the list at
  `/admin/structure/config_blocker_entity`.
* Currently available Drush commands:
   * client_config_care:generate_fixtures (ccc:gf)
      * Generates fixture config blocker entities for testing and development purpose.
   * client_config_care:delete_all_blockers (ccc:dab)
      * Deletes all config blockers.
   * client_config_care:show_all_blockers (ccc:sab)
      * Shows all available config blockers.
   * client_config_care:delete_config_blocker_by_name (ccc:dbn)
      * Deletes a specific config blocker by config name.
   * client_config_care:is_activated (ccc:ia)
      * Shows if Client Config Care is activated. It is recommended to disable Client Config Care on development
        environments.
* Execute `drush | grep ccc` to see all available Drush commands.

MAINTAINERS
-----------

 * Peter Majmesku - https://www.drupal.org/u/peter-majmesku

Supporting organization:

 * publicplan GmbH - https://www.drupal.org/publicplan-gmbh
