# CONTENTS OF THIS FILE

 * Introduction
 * When would you need this?
 * Installation
 * How To Use It
 * Maintainers

## Introduction

__This is not a module, you can't enable it.__

It provides drush commands for setting up or fixing your local environment
to have proper file permissions. It takes care of creating sites/default/files
and sites/default/private for your Drupal installation and updating them
in variables table, so you don't have to do that through the UI. It also takes
care of creating .htaccess files in those subdirectories for security.

To set permissions correctly, module detects your apache user and group automatically.

## When would you need this?

You might be seeing one of below notices:
```
  The directory sites/default/files is not writable.
  The directory sites/default/private is not writable.
  You may need to set the correct directory at the file system settings page
  or change the current directory's permissions so that it is writable.
```

```
  The CTools CSS cache directory, ctools/css could not be created due
  to a mis-configured files directory. Please ensure that the files directory
  is correctly configured and that the web server has permission to create
  directories.
```

## Installation

1. Download module to your `~/.drush` folder:
   `drush dl file_permissions --destination=~/.drush`
2. Run `drush cc drush` to clear Drush cache.

## How To Use It

By simply running `drush fp`
It is not recommended to use it with `sudo` as it will create `~/.drush/cache`
files that are owned by root user.

## Maintainers

Current maintainers:
 * Tomasz Turczynski (Turek) - https://www.drupal.org/user/412235

This project has been sponsored by:
 * Catch Digital - https://www.drupal.org/node/1482180
   Digital creative agency based in Central London. The company was incorporated in December 2006.
