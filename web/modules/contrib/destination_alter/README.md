# Drush Destination Alter

**THIS IS NOT A MODULE. YOU CAN'T ENABLE IT. FOLLOW THE INSTRUCTIONS.**

Manage your Drush downloads using the `--uri` (shortcut: `-l`) option. With this alteration you able to do the following:

## Installation

You can just `drush dl destination_alter` and Drush will download it into your `~/.drush` folder (alternately, you can obtain the package in another way and copy it into `~/.drush` by yourself.)

## Usage

Using this development, all modules will be downloaded to `modules/contrib` directory. The main thing: the `sites/default` or `profiles/<NAME>` could be used as usual subsite directory.

### Download package to active installation profile

```shell
drush dl file_md5 -l profile -y
```

### Download package to subsite

```shell
drush dl file_md5 -l [all|default|example.com] -y
```

### Use drushrc.php to set default value for --uri option

```php
<?php
/**
 * @file
 * Drush configuration.
 *
 * @var array $options
 */

// Specify a particular multisite.
// @see http://www.drupalcontrib.org/api/drupal/contributions!drush!examples!example.drushrc.php/7
$options['l'] = 'default';
```
