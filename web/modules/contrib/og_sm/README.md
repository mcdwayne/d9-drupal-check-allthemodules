# Organic Groups : Site Manager
This module provides support to setup a platform supporting multiple (sub)sites
based on [Organic Groups][link-og] (OG) functionality.

* Define what node types should be used as Site's.
* Simplified OG API by providing OG function wrappers.

> *NOTE* : Site entities are limited to node entities.



## Functionality
This module and its submodules adds functionality to support:

### Included in og_sm module
* Support for Group types to become sites enabled (Site).
* Support for Group Content types (Site Content).
* Support for Group Users (Site Users).
* Yml file discovery for site links.

### Included in og_sm_access module
* Access to Site content based on the publication status of the Site they
  belong to.

### Included in og_sm_admin_menu module
* Site administration menu that replaces the default admin toolbar when the user
  is in a Site context.

### Included in the og_sm_breadcrumb module
* Global breadcrumb behaviour configuration:
* When the og_sm_feature module is enabled: Configurable breadcrumb per Site.

### Included in og_sm_comment module
* Site comment administration

### Included in og_sm_content module
* Site content administration

### Included in og_sm_context module
* OG context detection based on:
  - Path alias of the current page.
  - Paths starting with group/node/NID

### Included in og_sm_dashboard module
* Provides blocks to be used with the dashboard module.

### Included in og_sm_feature module
* Enable/Disable & configure Site features.
  - Define globally default state & configuration.
  - Enable/Disable per Site.
  - Configuration per Site.

### Included in og_sm_global_roles module
* Dynamically grant user global roles when they have specific Site roles.

### Included in og_sm_menu module
* Centralised functionality to provide Site specific menu items.

### Included in og_sm_path module
* Define a Site path prefix per Site.
* Automatic path aliasing with the Site path as staring point.
* Auto update of Site content aliases and Site related page aliases when the
  Site path changes.
* Altering the `group/node/nid/admin/...` paths to `[site-path]/admin/...`.

### Included in og_sm_site_clone module
* A "Clone" tab on Site node detail/edit pages.
* A form to clone an existing Site (`node/[existing-site-nid]/clone`).
* Hooks so modules can alter prepared cloned Site and perform actions after a
  cloned Site is saved.

### Included in og_sm_taxonomy module
* Support global vocabularies with Site specific terms.
* Manage terms per Site.
* Select only from terms within the Site when creating content.

### Included in og_sm_theme module
* Set the theme per Site.
* Configure the breadcrumb for a theme within a Site.

### Included in og_sm_user module
* Site feature that creates site specific user profiles.
* Allow Sites to disable the editing of profiles, eg. when no alterable
  sections are available.

### Included in og_sm_user_create module
* Allow users to be created from within a Site context.

### Included in og_sm_variable module
* Store Site specific settings in og_sm_variable table.
* Get/Set/Delete Site specific variables.



## Requirements
The Sites functionality is build upon [Organic Groups][link-og].

Following modules are required to use the Sites functionality:

* [Organic Groups][link-og]



## Installation
Enable the Organic Groups Site Manager module.

Edit the node type settings of the types that should be Site types.
Enable:
* The Organic Groups > Group checkbox
* And the Site Manager > Site Type checkbox.



## API

> *NOTE* : In the following examples the og_sm services are accessed directly for
sake of simplicity, however it is recommended to access these using [dependency 
injection][link-dependency-injection] whenever possible.

### Load a Site node
Get a Site node by its Node ID (nid). Will only return the node object if the
node exists and it is a Site node type.
```php
$site = \Drupal::service('og_sm.site_manager')->load($site_nid);
```

### Currently active Site
A lot of code depends if we are currently in an active Site context.

A helper method is available to get the currently active Site node.
This is a wrapper around the `OgContext::getGroup()` method + loading the node.

Get the currently active Site node:
```php
$site = \Drupal::service('og_sm.site_manager')->currentSite();
```

### Clear all cache for Site
Clear all cache for one site.

This method does not clear the cache itself, it triggers the
`SiteEvents::CACHE_CLEAR` event so modules can clear their
specific cached Site parts

```php
$site = \Drupal::service('og_sm.site_manager')->clearSiteCache($site);
```

### Site types
Get a list of node types that are Site node types:
```php
$site_types = \Drupal::service('og_sm.site_type_manager')->getSiteTypes();
```

### Check if node is a Site
The module provides helper methods to detect is a node or node type is a Site
node type:

Check if the given node is a Site type:
```php
$is_site = \Drupal::service('og_sm.site_manager')->isSite($node);
```

### Check if a node type is a Site type
Check if the given node type is a Site type:
```php
$is_site_type = \Drupal::service('og_sm.site_type_manager')->isSiteType($type);
```

### Get the path to the Site homepage
Get the url to the homepage of a Site. This will return by default the path to
the detail page of the Site. Modules can implement
`hook_og_sm_site_homepage_alter()` to alter the path.

The function will return the url instance based on the given Site or, if no Site is
provided, the current Site (from OG context) will be used.

```php
$homepage_url = \Drupal::service('og_sm.site_manager')->getSiteHomePage($type);
```

### Site content types
Helper method to get a list of site type objects that can be used to create
content within a site.

```php
$site_content_types = \Drupal::service('og_sm.site_type_manager')->getContentTypes();
```

### Check if a content type can be used within a Site
Helper method to check if a given content type can be used to create content 
within a Site.

```php
$is_site_content_type = \Drupal::service('og_sm.site_type_manager')->isSiteContentType($type);
```

### Check if content belongs to a Site
Helper methods to get the Site (if any) of a given content item (node) belongs
to.

Get all the Site nodes a content entity belongs to.
```php
$sites = \Drupal::service('og_sm.site_manager')->getSitesFromEntity($entity);
```

Get the Site node object from a given site content entity object.
If a entity belongs to multiple Sites only the first Site will be returned.

```php
$site = \Drupal::service('og_sm.site_manager')->getSiteFromEntity($entitiy);
```

Check if the given entity belongs to a Site:
```php
$is_site_content = \Drupal::service('og_sm.site_manager')->isSiteContent($entitiy);
```

Check if the given entity belongs to a given Site:
```php
$is_member = \Drupal::service('og_sm.site_manager')->contentBelongsToSite($entitiy, $site);
```

### Check if user is member of a Site
Helper methods about the Sites a user is member of.

Get the Site nodes a given user belongs to:
```php
$sites = \Drupal::service('og_sm.site_manager')->getUserSites($account);
```

Check if a user is member of the given site:
```php
$is_member =  \Drupal::service('og_sm.site_manager')->userIsMemberOfSite($account, $site);
```

### module_name.site_links.menu.yml
The naming of the .yml file should be `module_name.site_links.menu.yml`

The allowed parameters per menu item are the same as core's `module_name.links.menu.yml`
file. Dynamic route parameters like the group's entity type (`{entity_type_id}`)
and the site node (`{node}`) are automatically injected when needed.

Example:

```yml
og_sm.site.admin:
  title: 'Administer site'
  route_name: entity.node.og_admin_routes
  menu_name: og_sm_admin_menu
og_sm.site.structure:
  title: 'Structure'
  route_name: og_sm.site.structure
  parent: og_sm.site.admin
  menu_name: og_sm_admin_menu
  weight: 30
  options:
    attributes:
      class:
        - 'toolbar-icon-system-admin-structure'
```

### hook_og_sm_site_menu_links_discovered_alter(&$items)
Alter the menu items as gathered using `module_name.site_links.menu.yml`.


## Events
The og_sm module triggers multiple events to make it easier to alter
functionality when a Site is involved.


### Clear all Site cache
When `SiteManager::clearSiteCache()` is called, it will not clear any cache
itself. It will trigger the  `SiteEvents::CACHE_CLEAR` event
so modules can clear the Site parts they have cached.

* `SiteEvents::CACHE_CLEAR` : Cache clear method is called for the given Site.


### Site node type action events
The module triggers events when a node type is being added or removed as being a
Site node type.

* `SiteTypeEvents::ADD` : Site node type is being added as a Site type.
* `SiteTypeEvents::REMOVE` : Site node type is no longer a Site type.


### Site action events
The module watches actions taken place on Site nodes and triggers its own events
when an action happens:

* `SiteEvents::PRESAVE` : Site node being prepared to be inserted or
  updated in the database.
* `SiteEvents::INSERT`  : Site node being inserted.
* `SiteEvents::UPDATE`  : Site node being updated.
* `SiteEvents::SAVE`  : Act on a Site node being saved. Will be
  triggered after a node is inserted or updated. It will always be called after
  all the `SiteEvents::INSERT`/`SiteEvents::UPDATE` events listeners are processed.
* `SiteEvents::DELETE`  : Site node being deleted.

There are also special post-action events available: the default action hooks
(insert, update, save and delete) are called during a DB transaction. This means
that it is not possible to perform actions based data in the database as all SQL
operations are not committed yet.

To allow modules to interact with a Site node actions after the Site node & all
queries by implemented events are stored in the database, following extra action
events are triggered:

* `SiteEvents::POST_INSERT` : Site node is inserted in the DB and all
  `SiteEvents::INSERT` event listeners are processed.
* `SiteEvents::POST_UPDATE` : Site node is updated in the DB and all
  `SiteEvents::UPDATE` event listeners are processed.
* `SiteEvents::POST_SAVE` : Site is inserted or updated in the DB and all
  `SiteEvents::INSERT`, `SiteEvents::UPDATE`, and `SiteEvents::SAVE` event 
  listeners are processed.
* `SiteEvents::POST_DELETE` : Site is deleted from DB and all
  `SiteEvents::DELETE` event listeners are processed.



## Hooks
The og_sm module also provides multiple hooks to make it easier to alter
functionality when a Site is involved.

> The hooks can be put in the `yourmodule.module` OR in the
> `yourmodule.og_sm.inc` file.
> The recommended place is in the yourmodule.og_sm.inc file as it keeps your
> .module file cleaner and makes the platform load less code by default.


### The site node is viewed.
Will only be triggered when the node_view hook is triggered for a node type that
is a Site type.
```php
/**
 * Implements hook_og_sm_site_view().
 *
 * @param &$build
 *   A renderable array representing the entity content. The module may add
 *   elements to $build prior to rendering. The structure of $build is a
 *   renderable array as expected by drupal_render().
 * @param \Drupal\node\NodeInterface $site
 *   The site node.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the
 *   entity components.
 * @param $view_mode
 *   The view mode the entity is rendered in.
 *
 * @see hook_node_view()
 */
function hook_og_sm_site_view(array &$build, \Drupal\node\NodeInterface $site, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display, $view_mode) {

}
```


### Alter the Site homepage path.
The `SiteManager::getSiteHomePage()` method creates and returns the url instance
to the frontpage (homepage) of a Site. That homepage is by default the Site node
detail page (node/[site-nid]).

Implementations can require that the homepage links to a different page (eg.
group/node/NID/dashboard).

This alter function allows modules to alter that path.

```php
/**
 * Implements hook_og_sm_site_homepage_alter().
 *
 * @param \Drupal\node\NodeInterface $site
 *   The entity object.
 * @param string $route_name
 *   The route name.
 * @param array $route_parameters
 *   The route parameters.
 */
function hook_og_sm_site_homepage_alter(\Drupal\node\NodeInterface $site, &$route_name, array &$route_parameters) {
  $route_name = 'og_sm.site.dashboard';
}
```


[link-og]: https://www.drupal.org/project/og
[link-dependency-injection]: https://www.drupal.org/docs/8/api/services-and-dependency-injection/services-and-dependency-injection-in-drupal-8
