# Organic Groups : Site Clone
This module adds functionality to create a new Site based on an existing one.



## Functionality
This module provides:
* A "Clone" tab on Site node detail/edit pages.
* A form to clone an existing Site (`node/[existing-site-nid]/clone`).
* A hook to alter preparing a new Site node based on an existing one.
* A hook so modules can perform extra operations once a cloned Site is saved.



## Requirements
* Organic Groups Site Manager



## Installation
1. Enable the module.
2. Open the detail page of a Site.
3. Click the Clone tab.
4. Fill in the form and save the new, cloned, Site.



## API
### Clone a Site programmatically
A Site can be cloned programmatically:

```php
$site_original = og_sm_site_load($site_nid);
$site_new = og_sm_site_clone_object_prepare($site_original);

// Update Site properties and fields.
$site_new->title = 'Title of the cloned Site';


node_save($site_new);
```

`og_sm_site_clone_object_prepare()` performs the following actions:
 
* Clear Site object properties that are Site node specific.
* Process the object trough `node_object_prepare()`.
* Let other modules alter the prepared object using the 
  `hook_og_sm_site_clone_object_prepare_alter` hook.



## Hooks
The module provides hooks so modules can interact when a Site is cloned:

### `hook_og_sm_site_clone_object_prepare_alter(&$site_new, array $context)`
Alter the new Site node after it was prepared by cloning from an existing Site.

The context contains following variables:
* original_site: Site node object that is used as source for the clone.

```php
function mymodule_og_sm_site_clone_object_prepare_alter(&$site_new, array $context) {
  // Set the new title based on original site prefixed with "Clone of".
  $site_original = $context['site_original'];
  $site_new->title = t(
    'Clone of !title',
    array('!title' => $site_original->title)
  );
}
```


### `hook_og_sm_site_clone($site_new, $site_original)`
Perform an action after the cloned Site was saved into the database.
 
```php
function mymodule_og_sm_site_clone($site_new, $site_original) {
  // Clone a variable from original to new.
  og_sm_variable_set(
    $site_new->nid,
    'variable_name',
    og_sm_variable_get($site_original->nid, 'variable_name')
  );
}
```
