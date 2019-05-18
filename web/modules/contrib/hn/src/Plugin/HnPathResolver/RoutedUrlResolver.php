<?php

namespace Drupal\hn\Plugin\HnPathResolver;

use Drupal\Core\Url;
use Drupal\hn\HnPathResolverResponse;
use Drupal\hn\Plugin\HnPathResolverBase;

/**
 * This provides a 404 resolver.
 *
 * @HnPathResolver(
 *   id = "hn_routed_url"
 * )
 */
class RoutedUrlResolver extends HnPathResolverBase {

  /**
   * {@inheritdoc}
   */
  public function resolve($path) {
    $url = Url::fromUserInput('/' . trim($path, '/'));

    if (!$url->isRouted()) {
      return NULL;
    }

    if ($url->getRouteName() === '<front>') {
      $front_page = \Drupal::config('system.site')->get('page.front');
      $url = Url::fromUri('internal:/' . trim($front_page, '/'));
    }

    $params = $url->getRouteParameters();
    $entity_type = key($params);

    if ($entity_type) {
      return new HnPathResolverResponse(
        \Drupal::entityTypeManager()
          ->getStorage($entity_type)
          ->load($params[$entity_type])
      );
    }
    if (explode('.', $url->getRouteName())[0] === 'view') {
      return new HnPathResolverResponse(
        \Drupal::entityTypeManager()
          ->getStorage('view')
          ->load(explode('.', $url->getRouteName())[1])
      );
    }

    return NULL;
  }

}
