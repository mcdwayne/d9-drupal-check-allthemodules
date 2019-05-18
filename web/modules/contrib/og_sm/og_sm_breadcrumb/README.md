# Organic Groups : Breadcrumb
Support breadcrumbs that are Site aware and allows overriding the root of the
breadcrumb.


## Functionality

This module makes breadcrumbs Site aware (when a Site context is active):

* Option to override the root (home) link target of the breadcrumb to point to
  the Site frontpage instead of the platform home.
* Option to hide the breadcrumb on the Site homepage (same behaviour as the
  platform homepage).
* Option to add the current page title to the end of the breadcrumb.


### Site Feature
This module provides also a Site feature. When the Site Feature (og_sm_feature)
module is enabled, breadcrumb settings can be configured per Site.

It also allows to override the root of the breadcrumb by one or more (external)
links.



## Requirements
* Organic Groups Site Manager (og_sm).
* Optional Organic Groups Site Feature (og_sm_feature).



## Installation
1. Enable the module.
2. Configure the breadcrumb settings at `admin/config/group/breadcrumb`.

When the feature module is enabled:
1. Enable the module
2. Configure the default breadcrumb settings at
   `admin/config/group/features/breadcrumb`.
3. Configure the Site specific settings at
   `[SITE-PATH]/admin/features/breadcrumb`.



## API
### Setting the global breadcrumb configuration programmatically
The breadcrumb settings are stored in variables.

Global settings:
* **og_sm_breadcrumb_enable** : Enable breadcrumb for Sites (0/1).
* **og_sm_breadcrumb_hide_on_frontpage** : Do not show the breadcrumb on Site
  homepage (0/1).
* **og_sm_breadcrumb_force_home** : Force the Home link (root of the breadcrumb)
  to point to the Site frontpage when a Site context is active (0/1).
* **og_sm_breadcrumb_append_title** : Add the title of the current page to the
  end of the breadcrumb (0/1).

```php
variable_set('og_sm_breadcrumb_enable', 1);
```


### Setting the breadcrumb configuration when the feature module is enabled
The default settings use the same variables as the global ones.

The `og_sm_breadcrumb_enable` is not used. Enabling/disabling the feature by
default for new Sites is stored in:

* **og_sm_feature_breadcrumb** : Enable/disable the breadcrumb by default for
  new Sites (0/1).

The Site specific settings are stored in the Site variables:

* **og_sm_feature_breadcrumb** : Enable/disable the breadcrumb for the Site
  (0/1).
* **og_sm_breadcrumb_hide_on_frontpage** : Do not show the breadcrumb on Site
  homepage (0/1).
* **og_sm_breadcrumb_force_home** : Force the Home link (root of the breadcrumb)
  to point to the Site frontpage when a Site context is active (0/1).
* **og_sm_breadcrumb_append_title** : Add the title of the current page to the
  end of the breadcrumb (0/1).
* **og_sm_breadcrumb_override_root** : Should the root of the breadcrumb be
  replaced by custom part(s) (0/1).
* **og_sm_breadcrumb_override_root_parts** : Array of breadcrumb parts that will
  replace the root of the breadcrumb. Each part is an array with a `text` and
  `path` element.


```php
og_sm_variable_set($site->nid, 'og_sm_breadcrumb_override_root', 1);
og_sm_variable_set($site->nid, 'og_sm_breadcrumb_override_root_parts', array(
  array('text' => 'First part', 'path' => 'http://external.url'),
  array('text' => 'Second path', 'path' => 'internal/path'),
);
```


### Get the breadcrumb configuration for a Site
There is a helper function to get the breadcrumb configuration for a given Site:

```php
$settings = og_sm_breadcrumb_settings($site);
```

It will return an array with following info:

* **hide_on_frontpage** : Should the breadcrumb be hidden on Site frontpage.
* **force_home** : Should the "home" link be replaced by the Site homepage.
* **append_title** : Should the title of the current item be added to the
  breadcrumb.
* **override_root** : Should the first item of the breadcrumb be replaced by
  custom part(s).
* **override_root_parts** : Array of root part(s), containing title and URL, to
  prepend the breadcrumb with if the override_root option is enabled.
