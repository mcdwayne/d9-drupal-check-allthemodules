<?php

namespace Drupal\commerce_recent_purchase_popup\CacheContext;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;

/**
 * Defines the RandomNumberCacheContext service to cache lazy built content.
 *
 * Cache context ID: 'random_recent_pruchase_popup'.
 */
class RandomRecentPurchasePopupCacheContext implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Random number from 1 to 20');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($parameter = NULL) {
    return array_rand(range(1, 20)) + 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($parameter = NULL) {
    return new CacheableMetadata();
  }

}
