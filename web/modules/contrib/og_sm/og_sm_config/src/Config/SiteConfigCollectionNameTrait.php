<?php

namespace Drupal\og_sm_config\Config;

use Drupal\node\NodeInterface;

/**
 * Provides a common trait for working with site override collection names.
 */
trait SiteConfigCollectionNameTrait {

  /**
   * Creates a configuration collection name based on a site node.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   *
   * @return string
   *   The configuration collection name for a site.
   */
  protected function createConfigCollectionName(NodeInterface $site) {
    return 'og_sm.' . $site->getEntityTypeId() . '.' . $site->id();
  }

  /**
   * Converts a configuration collection name to a site entity.
   *
   * @param string $collection
   *   The configuration collection name.
   *
   * @return int
   *   The site node id.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown if the provided collection name is not in the format
   *   "og_sm.ENTITY_TYPE_ID.ENTITY_ID".
   *
   * @see self::createConfigCollectionName()
   */
  protected function getSiteIdFromCollectionName($collection) {
    preg_match('/^og_sm\.(.*)\.(\d*)$/', $collection, $matches);
    if (!isset($matches[1], $matches[2])) {
      throw new \InvalidArgumentException("'$collection' is not a valid site override collection");
    }
    return $matches[2];
  }

}
