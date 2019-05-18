# Organic Groups : Site Content
This module provides content management functionality within a Site context.


## Functionality

### Create content within the Site context
Add new content within a Site context:
* `[site-path]/content/add` : Overview of all content types a user can create
  within a Site.
* `[site-path]/content/add/\[node-type]` : Add new content of a specific content
  type.


### TIP: Aliases for node/NID/edit & node/NID/delete
This module does not provide path aliases for `node/NID/edit` and
`node/NID/delete` paths.

Install the [Extended Path Aliases][link-path_alias_xt] module to provide this
functionality.


### Manage content within a Site
Two new Site admin pages are provided by this module:
* `[site-path]/admin/content` : Overview of all content within the Site.
* `[site-path]/admin/content/my` : Preview of all content created by the logged
  in user.
* Allow users with the Organic Groups "administer site" to alter the authoring
  data (author, published status, last update date).



## Requirements
* Organic Groups Site Manager

Optional:
* Organic Groups Administration menu : this module adds extra menu items to
  quickly add new content.
* Organic Groups Path : all content related paths
  (`group/node/[nid]/content/add/...`) are rewritten to
  `[site-path]/content/add/...`.
* [Extended Path Aliases][link-path_alias_xt] : Will add an
  `[content-path]/edit` and `[content-path]/delete` alias for all Site content.



## Installation
1. Enable the module.



## API

### Check if user has access to create content type
Check if a user has access to create new content of the given type within a
Site.

Check for currently logged in user:
```php
$has_access = og_sm_content_add_content_access($site, 'article');
```

Check for provided user:
```php
$has_access = og_sm_content_add_content_access($site, 'article', $account);
```


### Get a list of node types a user can create
Get a list of content types the user can create within a Site:.

Get for the currently logged in user:
```php
$list = og_sm_content_get_types_by_site($site);
```

Get for the provided user:
```php
$list = og_sm_content_get_types_by_site($site, $account);
```


### Get the URI to create content within a Site
Create the URI to the node creation form for the specified Site and node type:

```php
$uri = og_sm_content_add_uri($site, 'article');
```


### Get the Site specific content type Info
The content type information and settings can be overwritten within a Site. Use
this function to get the information specific for the given Site.


The information can be collected by passing the content type object.
```php
$info = og_sm_content_get_type_info_by_site($site, $content_type);
```

Or by the content type machine name (type).
```php
$info = og_sm_content_get_type_info_by_site($site, 'article');
```



## Hooks

> The hooks can be put in the `yourmodule.module` OR in the
> `yourmodule.og_sm.inc` file.
> The recommended place is in the yourmodule.og_sm.inc file as it keeps your
> .module file cleaner and makes the platform load less code by default.


### Provide/alter the Site specific content type information

This module provides a hook to alter the content type information specific for
the usage within a Site.

Example:

```php
/**
 * Implements hook_og_sm_content_type_info_alter().
 *
 * @param object $type_info
 *   The content type info as object.
 * @param object $site
 *   The Site node object.
 */
function og_sm_content_og_sm_content_type_info_alter(&$type_info, $site) {
  $key_base = 'og_sm_content_' . $type_info->type . '_';

  $type_info->site_type = check_plain(
    og_sm_variable_get($site->nid, $key_base . 'machine_name', $type_info->type)
  );
  $type_info->name = check_plain(
    og_sm_variable_get($site->nid, $key_base . 'name', $type_info->name)
  );
  $type_info->name_plural = check_plain(
    og_sm_variable_get($site->nid, $key_base . 'name_plural', $type_info->name)
  );
}
```



## Override content administration overview pages
This module provides 2 content overview pages within a site:
* All content (group/node/[site-nid]/admin/content) : Overview of all 
  content within a Site.
* My content (group/node/[site-nid]/admin/content/my) : Overview of all 
  content within a Site created by the currently logged in user.
  
The view and display in use for these pages can be altered by setting the
variable containing the setting. Each variable has the following value:
`view_name:display_name`.

The variable names are:

* `og_sm_content_view_admin_overview` : The all Site content overview
  (default `og_sm_content_admin_overview:embed_overview`).
* `og_sm_content_view_admin_overview_my` : The my Site content overview
  (default `og_sm_content_admin_overview:embed_overview_my`).



## Contributed module support
This module alters contributed modules so they support the new node/add paths:

* [addanother][link-addanother] : Make sure that the path to add another node
  within a Site stays within the current Site context.



[link-path_alias_xt]: https://www.drupal.org/project/path_alias_xt
[link-addanother]: https://www.drupal.org/project/addanother
