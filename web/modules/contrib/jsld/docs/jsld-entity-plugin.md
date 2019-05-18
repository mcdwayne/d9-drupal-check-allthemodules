# Entity Plugin

Entity plugins is attached for specific entity type and their bundle\view-mode combinations.

It's used when you need to add JSON-LD on pages where specific entity is presented.

**Annotation example**

```php
/**
 * @JsldEntity(
 *   id = "node_news",
 *   entity_type = "node",
 *   entity_limit = {"news|*", "page|full"}
 * )
 */
```

- id: Machine name for plugin.
- entity_type: Entity type ID on which this plugin is fired.
- entity_limit: An array of limitation inside entity type. Have structure `BUNDLE|VIEW_MODE`. Supports for wildcard `*` in both parts. If you set `*|*`, that plugin will be attached to all entities of this type, on every view mode.

This plugins is stored in `/src/Plugin/jsld/entity/` path.

## Generate plugin

```sh
drush generate plugin-jsld-entity
```

## Example of plugin

```php
<?php

namespace Drupal\MODULENAME\Plugin\jsld\entity;

use Drupal\Component\Utility\UrlHelper;
use Drupal\image\Entity\ImageStyle;
use Drupal\jsld\Plugin\jsld\JsldEntityPluginBase;

/**
 * @JsldEntity(
 *   id = "node_news",
 *   entity_type = "node",
 *   entity_limit = {"news|*"}
 * )
 */
class NodeNews extends JsldEntityPluginBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $host = \Drupal::request()->getSchemeAndHttpHost();
    $date_formatter = \Drupal::service('date.formatter');
    $result = [
      '@context' => 'http://schema.org',
      '@type' => 'Article',
      'name' => $this->entity->label(),
      'headline' => $this->entity->label(),
      'url' => $this->entity->toUrl('canonical', ['absolute' => TRUE]),
      'mainEntityOfPage' => $this->entity->toUrl('canonical', ['absolute' => TRUE]),
      'datePublished' => $date_formatter->format($this->entity->created->value),
      'dateModified' => $date_formatter->format($this->entity->changed->value),
      'author' => [
        '@context' => 'http://schema.org',
        '@type' => 'Organization',
        'name' => \Drupal::config('system.site')->get('name'),
        'sameAs' => $host,
        'url' => $host,
      ],
      'publisher' => [
        '@context' => 'http://schema.org',
        '@type' => 'Organization',
        'name' => \Drupal::config('system.site')->get('name'),
        'sameAs' => $host,
        'url' => $host,
      ],
    ];

    if (!$this->entity->field_news_promo->isEmpty()) {
      $image_url = ImageStyle::load('medium')
        ->buildUrl($this->entity->field_news_promo->entity->uri->value);
      $url = new UrlHelper();
      // This helps to use URL without query parameters.
      $url_parts = $url->parse($image_url);
      $result['image'] = [
        '@type' => 'ImageObject',
        'url' => $url_parts['path'],
      ];
    }
    return $result;
  }

}
```