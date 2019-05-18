# Organic Groups : Features
Adds an API and interface to disable/enable features per Site.



## Functionality
### Detect features
Provides a hook system to allow modules to provide this module information about
features.


### Enable/Disable features
Interface per Site to enable/disable available features.


### API
API to retrieve the status of a feature.


### Views access plugin
Views access plugin to validate if the feature is enabled (OG SM Feature).


### Context condition plugin
Context condition plugin to validate if a Site has one or more features enabled.



## Requirements
* Organic Groups Site Manager
* Organic Groups Site Variable



## Installation
1. Enable this module.
2. Define the features for a module.



## API
### Get information of all features
Get information about all features.

```php
$info = og_sm_feature_info();
```


### Get information of a feature
Get information about a feature.

```php
$info = og_sm_feature_feature_info('feature name');
```

### Check if a feature exists
Check if a feature exists by its name.

```php
$exists = og_sm_feature_exists('feature name');
```


### Enable a feature for a Site
Enable a feature for the given Site.

```php
og_sm_feature_site_enable($site, 'feature name');
```


### Disable a feature for a Site
Enable a feature for the given Site.

```php
og_sm_feature_site_disable($site, 'feature name');
```


### Check if feature is enabled
Check if a feature is enabled for a given Site.

```php
$is_enabled = og_sm_feature_site_is_enabled($site, 'feature name');
```


### Check if a content type is enabled
Check if a content type is enabled within a Site. This is done by checking if
one of the features the content type belongs to is enabled.

Content types that don't belong to any feature will always be seen as enabled.

```php
$is_enabled = og_sm_feature_site_content_type_is_enabled($site, 'news');
```


### Check if a vocabulary is enabled
Check if a vocabulary is enabled within a Site. This is done by checking if one
of the features the vocabulary belongs to is enabled.

Vocabularies that don't belong to any feature will always be seen as enabled.

```php
$is_enabled = og_sm_feature_site_vocabulary_is_enabled($site, 'tags');
```


### Access callback based on feature status
This module provides an access callback (use in menu callbacks) to grant access
based on the fact that a feature is enabled.

Use without providing the Site, this will use the current Site (if any) from OG
context.

```php
$has_access = og_sm_feature_access('feature name');
```

Optionally pass the Site object:

```php
$has_access = og_sm_feature_access('feature name', $site);
```


### Returns a renderable form array for a given form ID and feature.
Helper function to create a feature form callback, this callback can be used
in a similar way as `drupal_get_form` in `hook_menu()`.

The following example would create a global version of the user feature form.
```php
$items['admin/config/group/features/user'] = array(
  'title' => 'User feature',
  'page callback' => 'og_sm_feature_get_form',
  'page arguments' => array('og_sm_user_feature_form', 'user'),
);

```

To create a site version of the form simply add the site node to the page
arguments.
```php
$items['group/%/%og_sm_site/admin/features/user'] = array(
  'title' => 'User feature',
  'page callback' => 'og_sm_feature_get_form',
  'page arguments' => array('og_sm_user_feature_form', 'user', 2),
);

```



## Hooks
> The hooks can be put in the `yourmodule.module` OR in the
> `yourmodule.og_sm.inc` file.
> The recommended place is in the yourmodule.og_sm.inc file as it keeps your
> .module file cleaner and makes the platform load less code by default.


### Inform the platform about feature(s)
The module provides a hook to allow modules to inform about their feature(s).

The info hook should return an array with an info array per feature (one info
hook can return multiple features).

The info array contains following information:
* **title** : The feature title.
* **description** : The feature description.
* **global configuration** : An optional path to the a configuration page to set the
  global defaults for a feature.
* **site configuration** : An optional path to change the configuration of the
  feature specific for the Site. The path should be specified without the
  `group/node/NID/` path prefix as it will be appended automatically.
* **content types** : An optional array of content types (machine names) that
  belong to the feature. The content types will be hidden and access to create
  them will be declined if it belongs to a feature and that feature is not
  enabled.
* **vocabularies** : An optional array of vocabularies (machine names) that
  belong to the feature. The vocabulary will be hidden from the Site taxonomy
  administration pages and access to them will be declined.

```php
function hook_og_sm_feature_info() {
  $items = array();

  $items['news'] = array(
    'name' => t('News'),
    'description' => t('News content and overviews.'),
    'global configuration' => 'admin/config/group/features/news',
    'site configuration' => 'admin/features/news',
    'content types' => array('news'),
    'vocabularies' => array('tags', 'categories'),
  );
  $items['articles'] = array(
    'name' => 'Articles',
  );

  return $items;
}
```


### Alter the feature(s) info
Hook to alter the information collected from the hook_og_sm_feature_info()
hooks.

```php
function hook_og_sm_feature_info_alter(&$info) {
  $info['news']['site configuration'] = 'admin/features/news-test';
}
```


### Defines an array of default values for a feature settings form.
The site argument is optional. If empty the global defaults are will be fetched.

```php
function hook_og_sm_feature_form_defaults($feature, $site = NULL) {
  return array(
    'user' => array(
      'title' => t('Users'),
    ),
  );
}
```


### Alters the default values collected by hook_og_sm_feature_form_defaults().
The site argument is optional. If empty the global defaults are will be fetched.

```php
function hook_og_sm_feature_form_defaults_alter(&$defaults, $feature, $site = NULL) {
  $defaults['user']['title'] = t('Site users');
}
```
