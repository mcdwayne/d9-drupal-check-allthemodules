<?php

namespace Drupal\ofed_switcher\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\RequestStackCacheContextBase;

/**
 * Defines the PathIsAdminCacheContext service.
 *
 * Cache context ID: 'path_is_admin'.
 */
class PathIsAdminCacheContext extends RequestStackCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Path is admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return \Drupal::service('router.admin_context')->isAdminRoute();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
