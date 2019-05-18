# Drupal 7 to 8 media migration

This module implements a migration path for Drupal 7 sites
using media module to Drupal 8.

It performs the following:

* For media bundles which exist in the destination, it creates and
  attaches its fields along with their configuration.
* Alters content migrations so the data is migrated.
* Transforms image fields to media image fields.

# Before running the below commands

1. Install media module in Drupal 8.

2. At the moment this module cannot create media bundles for you because
  I (juampynr) could not figure out how to map a file type source in
  Drupal 7 with a media source in Drupal 8. Therefore, if your
  Drupal 7 project has other file types apart from the default ones,
  configure them in Drupal 8 and respect the entity identifiers. Then
  this module will be able to create and attach the fields plus
  prepare the content migration. 

# Usage

The following requires the `migrate_upgrade` module.

1. Require this module via composer so it downloads all of its dependencies.

```bash
composer require drupal/media_migration
```

2. Install it.

```bash
drush pm:enable media_migration --yes
```

3. Recreate migration files and export them. The `legacy-db-key` is the key
of the database connection of the source database (the Drupal 7 one).

```bash
drush migrate:upgrade --legacy-db-key=drupal7 --legacy-root=sites/default/files --configure-only
drush config:export --yes
```

4. Run configuration migrations:

```bash
drush migrate:import --tag=Configuration --execute-dependencies
```

5. Run content migration:

```bash
drush migrate:import --tag=Content --execute-dependencies
```
