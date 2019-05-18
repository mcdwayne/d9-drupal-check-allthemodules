# Path Plugin

Path plugins is attached for specific pages applies on URL paths.

It's used when you need to add JSON-LD on pages with specific paths.

**Annotation example**

```php
/**
 * @JsldPath(
 *   id = "organization",
 *   match_type = "listed",
 *   match_path = {"/about", "<front>", "/info", "/info/*"}
 * )
 */
```

- id: Machine name for plugin.
- match_Type: (listed, unlisted) The way how paths logic will be used. If set to listed, than this JSON-LD will be applied only on listed pages from `match_path`, for unlisted otherwise, on all pages, except listed.
- match_path: An array of paths. Supports for wildcard `*` and `<front>`. If you set `*`, that plugin will be attached to all pages. Paths must have leading `/`.

This plugins is stored in `/src/Plugin/jsld/path/` path.

## Generate plugin

```sh
drush generate plugin-jsld-path
```

## Example of plugin

```php
<?php

namespace Drupal\MODULENAME\Plugin\jsld\path;

use Drupal\jsld\Plugin\jsld\JsldPathPluginBase;

/**
 * @JsldPath(
 *   id = "organization",
 *   match_type = "listed",
 *   match_path = {"*"}
 * )
 */
class Organization extends JsldPathPluginBase {

  /**
   * @return array
   *   The JsonLD array.
   */
  public function build() {
    $host = \Drupal::request()->getSchemeAndHttpHost();
    return [
      '@context' => 'http://schema.org',
      '@type' => 'Organization',
      'name' => \Drupal::config('system.site')->get('name'),
      'sameAs' => $host,
      'url' => $host,
    ];
  }

}
```