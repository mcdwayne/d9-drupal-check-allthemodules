CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * How to Use
 * Maintainers


INTRODUCTION
------------

The Hard-working Monkey module provides currently two little Drush commands to
check and fix fields with default values in node entities.

For example, when you add later a field to a content type and have existing
nodes of this type, the existing nodes get this field entity with it's default
value only on node save into database storage. This behavior can cause problems
for views, etc.

The hard-working monkey does this job for you. You can check and fix existing
fields with this little monkey.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/monkey

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/monkey


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.
Drush is required to execute drush commands.


INSTALLATION
------------

 * Install the Hard-working Monkey module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


HOW TO USE
----------

```
drush <monkey-command> <monkey-worktype> <entity-type> <bundle> [--fields=field_machine_name_1,field_machine_name_2] [--fast]
```

 * drush monkey-check fields node article
 * drush monkey-fix fields node article --fields=field_with_default_value
 
 Drupal 7 only:
 
 * drush monkey-fix fields node article --fields=field_with_default_value --fast (EXPERIMENTAL)
 

MAINTAINERS
-----------

 * IT-Cru - https://www.drupal.org/u/IT-Cru
 * killes@www.drop.org - https://www.drupal.org/u/killeswwwdroporg
