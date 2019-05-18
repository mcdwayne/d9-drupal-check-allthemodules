INTRODUCTION
------------

# Node Access Timestamp by User

## Description
------------

This module creates a database table which stores the last access **timestamp** per node per user.

### This module provides:
  - Custom database table `node_access_timestamp_by_user`
  - Custom view

### What this module does:
  - Creates a database table on install
  - During hook_preprocess_node(), gets current **uid**, **nid**, and **timestamp**
  - Checks if a table row with current **uid** and **nid** exists
  - If a row contains current **uid** and **nid**, overwrites associated **timestamp**
  - If row does not exist, creates a new entry with **uid**, **nid**, and **timestamp**
  - Creates a custom views block

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/node_access_timestamp_by_user

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/node/add/project-issue/node_access_timestamp_by_user


## REQUIREMENTS
------------

This module requires the following modules:

 * Node
 * User


## INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.
   
1. **Install** this module


## CONFIGURATION
------------

1. Configuration is handled by the view creation/settings
2. Views must be set to not cache (under advanced tab)

## Custom Database Table
------------

This table is updated via HOOK_proprocess_node() and builds the data in the following structure:

  - uid
  - nid
  - timestamp

Database table name: `node_access_timestamp_by_user`

* _Our timestamp is stored in a database table with associated uid and nid_.

## Custom View
------------

Provides a view group named **Node Access Timestamp by User**, select this under _view settings_ when creating a new view.

### Default available fields:
  - uid
  - nid
  - timestamp

### Default available relationships:
  - uid
  - nid

## MAINTAINERS
-----------

Current maintainers:
 * Preston Schmidt - https://www.drupal.org/user/3594865
