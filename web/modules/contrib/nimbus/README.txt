Nimbus Configuration Management

Nimbus is a configuration management tool aimed at extending
the configuration import functionality of Drupal core to support
the import from multiple concurrent configuration directories for
sophisticated configuration deployment workflows
using dependency management tools.

Nimbus enhances and replaces the regular configuration synchronization
process and the "drush config-import" command.

Example Usage

If you're working with multiple websites
using a shared codebase that only differ slightly in
configuration (site name, email address, various API keys etc.),
nimbus enables you to keep most of your site configuration as part
of the shared codebase and only have truly site-specific configuration files
in your site-specific repository. Alternatively, you can have site-specific
configuration files override shared configuration files. Additionally, it makes
it possible to keep your configuration in your module and profile repositories,
if you so choose.

So you might have the following configuration directory set-up:

// Nimbus config override settings.
global $_nimbus_config_override_directories;

$_nimbus_config_override_directories = [
  '../config/shared',     // Global drupal configuration directory.
  '../config/override',   // Project specific drupal configuration directory.
  '../config/local',      // Local configuration directory.
  '../config/export',     // Configuration export directory.
];

When running a configuration synchronization or
the "drush config-import" command, configuration files
will be imported in the following order:

1. Configuration files in any module's /config/install directory
2. Configuration files in the used install profile's /config/install directory
3. Configuration files in the configured CONFIG_SYNC_DIRECTORY
4. Configuration files in the configured nimbus configuration directories,
   in specified order.

Each configuration file will override its possibly previously found
configuration files of the same name higher up the chain.

Configuration export via the "drush config-export" command will export
the whole site configuration into the last/lowest specified
configuration directory.
