# Organic Groups : Site Access
The Access module provides node_access implementations to limit access to Sites
and their content based on the publish status of the Site node.


## Functionality
This Site Manager submodule provides:
* Block access to Sites and their content that are not published.
* Provide extra group permissions to grant certain roles access to unpublished
  Sites and their content.
* Start batch to recalculate all node grants for the content of a Site if the
  published state of that Site changes.



## Requirements
The Sites functionality is build upon [Organic Groups][link-og].

Following modules are required to use the Site Access functionality:

* Organic Groups
* OG Site Manager
* Node access



## Installation
* Enable the Organic Groups Site Manager Access module.
* Update the group permissions:
  * grant the proper user roles within groups the "View an unpublished Site and
    its content" permission.



## API
The module implements the node access hooks to provide the access checks.

Next to that it provides 2 access callbacks to use for custom menu items:


### og_sm_access_callback
Check if the user has access to the currently active Site (if any):

```php
function mymodule_menu() {
  $items = array();
  $items['my-page'] = array(
    'title' => t('My module access callback demo'),
    'page callback' => 'mymodule_page_callback',
    'access callback' => 'og_sm_access_callback',
  );
  return $items;
}
```

### og_sm_access_site_nid_callback
Check if the user has access to a Site by passing its node id as argument:

```php
function mymodule_menu() {
  $items = array();
    'title' => t('My module access callback demo'),
    'page callback' => 'mymodule_page_callback',
    'access callback' => 'og_sm_access_site_nid_callback',
    'access arguments' => array($node->nid),
  return $items;
}
```
