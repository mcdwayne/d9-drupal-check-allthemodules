# YouTrack integrated into Drupal

With this module you can directly report issues into JetBrains issue tracking and project management system YouTrack (http://www.jetbrains.com/youtrack/) using their comprehensive API.

This module currently supports:

* Watchdog log entries
* Issue created directly from your custom module
* Rules action to create an issue
* Providing an interface to nepda/youtrack-client library you can use for wide range of API calls (see https://github.com/nepda/youtrack/tree/master/examples)

## Installation

In order to use the module nepda/youtrack-client library should be hooked with composer. In case you don't have it installed automatically, you can use Composer Manager module (and composer tool itself) to organize modules' composer dependencies, YouTrack specifically.

```
drush dl composer_manager
drush en -y composer_manager
php modules/contrib/composer_manager/scripts/init.php
composer drupal-rebuild
composer drupal-update
```

You may want to add the dependency directly:

```
composer require nepda/youtrack-client "1.2.*"
```

Then you can enable the module and YouTrack UI and proceed to the configuration page (admin/config/system/youtrack). Enter the following parameters:
* URL - YouTrack installation URL. Do not include trailing slash or /rest suffix. For instance: http://localhost:8080
* API User Login and API User Password - credentials of the account you're going to use for actions executed by the module.

That's it, you're able to enable YouTrack Logger module or add the Rules action to your own rules. And of course write your own code to manage YouTrack data:
```
\Drupal::service('youtrack.connection')->getConnection()->getAccessibleProjects();
\Drupal::service('youtrack.connection')->getConnection()->getUsers();
```

## Contribution

The module is in development state, so any feedback is welcome.

* Issue queue: https://www.drupal.org/project/issues/youtrack
* Contact maintainers directly:
  * Sergey Susikov - https://www.drupal.org/u/angerslave
  * Jürgen Haas - https://www.drupal.org/u/jurgenhaas
