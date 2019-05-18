# Organic Groups : Site Path
This module adds a field to the Site node form to set a Path prefix for the
Site.

That path is used as path alias for the Node and will be used to rewrite Site
related paths and URL's.



## Functionality
### Define a path per Site
Adds a Site Path field to the Site node form. That path will be used to create
content path aliases and to rewrite Site administration paths.

Changing existing Site paths can be limited by global and Site roles.


### URL alter
This module will automatically alter all outgoing URL's from:
* `/group/node/[nid]/admin/…` to `/[site-path]/admin/…`.
* `/entity_reference_autocomplete/*/*/*` to `[site-path]/entity_reference_autocomplete/*/*/*`
  (only if the URL is created within an active Site context).

It will transform incoming altered URL's back to its original path.


### URL query *destination* alter
It will check if an URL has a destination query parameter and will replace its
value by the proper path alias or URL outbound altered value.


### Update all aliases when a Site path changes
This module will update all aliases (content) when the
alias of the Site changes.


### Delete all aliases when a Site is deleted
This module will delete all existing aliases for content and pages related to
the Site.

This is done by deleting all aliases where the alias path starts with the Site
alias.


### Aliases for node/NID/edit and node/NID/delete
This module does not provide path aliases for `node/NID/edit` and
`node/NID/delete` paths.

Install the [Extended Path Aliases][link-path_alias_xt] module to provide this
functionality.


### OG Context provider by Site path
Context provider to detect the Group context based on the Site path of the
current page.

It will check if:
- The path alias (if any) starts with a known Site path.
- The path (if no alias) starts with a known Site path.

Content created within the Site needs to get a path prefixed with the Site path.
See installation instructions.



## Requirements
* Organic Groups Site Manager
* Organic Groups Site Variable
* [Pathauto](https://www.drupal.org/project/pathauto)
* [Token](https://www.drupal.org/project/token)



## Installation
1. Enable the module.
2. Configure the alias for content on admin/config/search/path/patterns:
   - Overall or per Site content types : `[node:site-path]/...`
3. Setup the OG context providers on admin/config/group/context:
   - Enable the "**Site Path**" detection method.
   - Put the "**Site Path**" detection method on the **first** place.
   If the og_sm_context module is used, make sure that the "**Site Path**"
   method is always set first.
4. Grant user roles access to edit existing Site paths.
5. Grant organic group roles access to edit existing Site paths.
6. Update all existing Sites: edit them and set their Site Path value.
7. Delete and regenerate all content aliases.



## API

> *NOTE* : In the following examples the og_sm services are accessed directly for
sake of simplicity, however it is recommended to access these using [dependency
injection][link-dependency-injection] whenever possible.q

### Get the path of a Site
Get the path of the given Site:
```php
$path = $site = \Drupal::service('og_sm_path.site_path_manager')->getPathFromSite($site);
```


### Get a Site by its path
Get the Site object by its path:
```php
$path = $site = \Drupal::service('og_sm_path.site_path_manager')->getSiteFromPath($path);
```


### Set the path for a site
Pragmatically set the path for a given site.

This will:
- Check if the new path is different from the current if so it will:
  - Set the path variable for the site.
  - Trigger an event `SitePathEvents:CHANGE` to inform the platform about the
    path change.
```php
\Drupal::service('og_sm_path.site_path_manager')->setPath($site, '/my-site-path');
```

Triggering the `SitePathEvents:CHANGE` event can be disabled:
```php
\Drupal::service('og_sm_path.site_path_manager')->setPath($site, '/my-site-path', FALSE);
```


## Events
### Site action hooks
The og_sm_path module triggers multiple events to make it easier to alter
functionality when a Site is involved.

* `SitePathEvents:CHANGE` : Site node path has changed. The event listener method
  receives a `SitePathEvent` instance.




[link-path_alias_xt]: https://www.drupal.org/project/path_alias_xt
