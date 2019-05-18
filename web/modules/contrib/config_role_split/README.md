# Config Role Split

This module works with [Config Filter](https://www.drupal.org/project/config_filter)
and only provides a ConfigFilter, as such it does not have an immediate effect
on a running site. It only comes into action when importing or exporting
configuration.

## Status and Weight
The status and weight are used by Config Filter. Only active filters are
applied and they are sorted by weight: Smaller (or negative) weights first
bigger weights last (they sink to the bottom of the list.)

## Modes

The following modes are currently implemented:

### Split
The roles and permissions are removed from the export and maintained only in
the configuration of the role split filter. When importing, the permissions
defined in the role are merged with the ones defined in the filter. When
exporting the permissions are *split* and the role will not have the permission
all the while the role in the active configuration will have it.

### Fork
The permissions defined in the filter configuration are merged when importing.
But when exporting the exported role is cheched first and permissions that
are already exported will not be removed from the export. Permissions that
are active in the sites configuration and defined in the filter but not in
the role in the sync directory are not added to the role in the sync directory.

### Exclude
The permissions that are attached to a role in the sync directory that are
defined in a exclude filter are removed and will not be part of the active
configuration when importing. When exporting the permissions which have been
exluded will be added back to the role in the sync directory, provided it
already has them there.

## Roles
Currently the form for entering the role configuration is very crude but works.

The roles configuration should be an array of roles with and array of
permissions. Not all the sites roles or all the active permissions need to be
listed, only the ones the filter should interact with.

Example:
```yaml
administrator:
  - 'say hello world'
  - 'do other things'
authenticated:
  - 'access user profiles'
```

The roles should be the id of the role and the permissions should be the same
string as the permission id.
