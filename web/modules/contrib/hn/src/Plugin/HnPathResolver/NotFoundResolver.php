<?php

namespace Drupal\hn\Plugin\HnPathResolver;

use Drupal\Core\Url;
use Drupal\hn\HnPathResolverResponse;
use Drupal\hn\Plugin\HnPathResolverBase;

/**
 * This provides a 404 resolver.
 *
 * @HnPathResolver(
 *   id = "hn_not_found",
 *   priority = -100
 * )
 */
class NotFoundResolver extends HnPathResolverBase {

  /**
   * {@inheritdoc}
   */
  public function resolve($path) {
    $url = Url::fromUri('internal:/' . trim(\Drupal::config('system.site')->get('page.404'), '/'));
    $params = $url->getRouteParameters();

    if (empty($params)) {
      throw new \Exception('The 404 page can\'t be loaded. Please check your config at /admin/config/system/site-information.');
    }

    $entity_type = key($params);
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($params[$entity_type]);
    return new HnPathResolverResponse($entity, 404);
  }

}
