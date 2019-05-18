# Advanced Access

Advanced Access is a module that provides and extendible api for drupal entity
access control.

## Why advacned access?

Advanced access provides a few extencible enhancements to the Drupal core
access control functionality. It allows modules that provide access control
functionalities to be entity agnostic, and EntityTypes to use all available
access provides to give site administrators more granualar control over the
actions of users across the site.

Advanced Access provides two types of plugins; Access Provides and Access
Consumers. Together, the two plugins can be used to provides access to any
entity type across the site.

## Extensibility

### Providers

Access Providers extend the concept used by Nodes Access to other entity types
being provided by modules. Provides use the same concept of access grants and
requirements based on a generic entity type.

### Consumers

Consusmers allow for Advanced access to be "enabled" for a given entity type.

#### Basic Plugins

These plugins provide a single major feature, the ability for the site admin
to configure the access provides to be used for an entity type. Functionality
and implmentation of access control beyond this is up to the providing module.

#### Advanced Plugins

In addition to the functions enabled through Basic Plugins, advanced plugins
tell Advanced access to override the default access control handler for a
given entity type. By doing so, we enable restricting content without any other
required changes to code. The Advanced Access plugin, will still leverage the
entity types original access handler, but apply access grants from available
providers to give the site builder the granularity required.

## Questions?

Please file any questions or feature requests for this module in the projects
issues queue on Drupal.org.

## About Module

The Advanced Access module was designed, developed, and is now maintained by
[Rich Gerdes](https://drupal.org/u/richgerdes).
