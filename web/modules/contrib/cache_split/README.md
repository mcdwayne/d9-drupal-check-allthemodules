# cache_split

The module provides a cache backend for Drupal to split cache items of a single bin
into separate backends.

## Installation

1. Download cache_split module or [add it via composer](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies)
2. Enable `cache_split` module (e.g. `drush en cache_split`)
3. Change the cache backend for your bin (e.g. `render`) in your _settings.php_
```php
<?php
$settings['cache']['bins']['render'] = 'cache.backend.split';
?>
```
4. Add split configuration for your bin to the _settings.php_:

```php
<?php
$settings['cache_split']['render'] = [
  //..
];
```

See _Configuration_ below for details.

## Configuration

The configuration for a cache bin has to be defined in the _settings.php_:
```php
<?php
$settings['cache_split']['NAME_OF_CACHE_BIN'] = [
  //..
];
```

### Matcher definition

Each bin can hold multiple matcher definitions, each may consists of:

* `backend`: Name of the cache backend service to use (e.g. `cache.backend.database`).
   If not given it defaults to the key of the definition.
* `includes`: Array of cid patterns this backend should be used for. If this is
   empty all cids are included (except those excluded by `excludes`).
* `excludes`: Array of cid patterns this backend should **not** be used for

#### Wildcard syntax

A cid pattern may use `*` to match any number of arbitrary characters.

#### Fallback cache backend

A fallback backend can be defined by simply omitting `includes` and `excludes` or
leaving them empty.

Make sure the fallback backend is defined last, so the other definitions are
considered.

In case no fallback backend is specified, `cache.backend.database`
is set as default.

### Example

```php
<?php
$settings['cache_split'] = [
  // Splits render cache in multiple backends.
  'render' => [
    // Do not cache render results for paragraphs, as they are only rendered in
    // context of the host entity.
    [
      'backend' => 'cache.backend.null',
      'includes' => [
        'entity_view:paragraph:*'
      ],
      'excludes' => [],
    ],
    // Falls back to database backend.
    [
      'backend' => 'cache.backend.database',
    ],
  ],
];
?>
```
