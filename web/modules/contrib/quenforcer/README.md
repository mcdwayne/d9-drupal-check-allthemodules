# Site Quota Enforcer

## Overview

This module enforces quotas set for the entire Drupal site. It was written for the [Aegir Hosting System](http://www.aegirproject.org/) to enforce quotas set by Aegir administrators on client sites, but could be used with any Drupal hosting system that prevents site users from editing settings.php, where the limits are set.

It works by preventing new entities from being creating on form submission if associated resources are at or past their limits.

Currently supported quotas:

* The number of users
* The total amount of storage (the combined storage for public and private files along with the database size)

Information on each quota (current amount, limit and percentage used) is presented on the site's main status page.

While this module runs on client sites, quota limits are set via the [Aegir Variables](https://www.drupal.org/project/hosting_variables) module that runs on the Aegir hostmaster site itself.

## Setting limits

To set a limit for a particular resource, click on a site's *Variables* tab within Aegir, add the key-value pair, and then Verify the site.  The site's Verify task will add the *settings.php* lines for you.

You can also manually add the desired lines to the site's *settings.php*.

Any quota whose limit has not been set will be ignored (both on the status page and during enforcement).

## Example Settings

### User quota

Either of these settings will prevent more than 4 users from being created.

#### Aegir

* Variable key: **config||quenforcer.settings~users_max_number**
* Variable value: **4**

#### settings.php

```php
$config['quenforcer.settings']['users_max_number'] = 4;
```

### Storage quota

Either of these settings will prevent nodes from being created when the amount of storage has reached 2 GB.

#### Aegir

* Variable key: **config||quenforcer.settings~storage_max_megabytes**
* Variable value: **2048**

#### settings.php

```php
$config['quenforcer.settings']['storage_max_megabytes'] = 2048;
```

## Notification and Enforcement

If at least one quota has reached 75% of its limit, the *Quotas* section on the status page will issue a warning (turn yellow).

If at least one quota has reached 100% of its limit, the *Quotas* section on the status page will issue an error (turn red), and associated entities will not be able to be created; their creation forms will not validate, and therefore not submit.
