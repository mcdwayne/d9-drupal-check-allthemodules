# Module Blacklist

This module is intended for site administrators. The module allows site
administrators to block certain module from being installed, based on a
blacklist set on settings.php file.

## Who is this for?
Drupal 8 comes with the powerful Configuration Management system and modules
like [config_split](https://www.drupal.org/project/config_split) and 
[readonlymode](https://www.drupal.org/project/readonlymode) can helps the sites
administrators to control the modules that are being installed and configured
through the environments. But sometimes, only basing your administration on CM
could be not enough, since the users could have access to the config files and
hooks like `hook_update` to enable certain non-desired modules programmatically.

The idea behind of this module is to provides more one additional security layer
allowing you to define and control a blacklist of modules that you don't want
to get installed at your environment(s).

## Usage and configuration
This module does not offer an administrative form, instead, its configuration
must be made directly in the `settings.php` file through the variable
`$settings['module_blacklist']` to define the list of modules that you want to
block the installation. 

```
$settings['module_blacklist'] = [
  'devel',
  'simpletest',
  'migrate_drupal_ui',
];
```

Why use settings.php instead of an admin form to save the blacklist?
The purpose of the settings.php file is to be specific to environments,
read-only and protected from users, so keep the list on there is more secure
than configs, where could be replaced. `$settings` variables are
read-only/Immutable.  

Once the list is defined, any attempt to enable the listed modules will throw an
exception and the process will terminate.

Also, the module alters the core admin form that list the available modules at
`/admin/modules` disabling the checkboxes and adding a warning message to the
blacklisted modules.

## Drush bypass
By design, any kind of attempt to enable the blacklisted modules will throw
an exception and kill the process, even using Drush. If you want to allow Drush
to enable modules (`drush pm-enable <module>`), add this settings to your
`settings.php`: `$settings['module_blacklist_drush'] = TRUE;`.
