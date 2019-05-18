<?php

namespace Drupal\micro_node\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Cache\Context\UserCacheContextBase;
use Drupal\node\Cache\NodeAccessGrantsCacheContext;

/**
 * Defines the node access view cache context service.
 *
 * Cache context ID: 'user.node_grants' (to vary by all operations' grants).
 * Calculated cache context ID: 'user.node_grants:%operation', e.g.
 * 'user.node_grants:view' (to vary by the view operation's grants).
 *
 * This allows for node access grants-sensitive caching when listing nodes.
 *
 * @see node_query_node_access_alter()
 * @ingroup node_access
 */
class MicroNodeAccessGrantsCacheContext extends NodeAccessGrantsCacheContext implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($operation = NULL) {
    /** @var \Drupal\Core\Cache\CacheableMetadata $cacheable_metadata */
    $cacheable_metadata = parent::getCacheableMetadata();
    // @TODO test setMaxAge(-1).
    return $cacheable_metadata;

  }

}
