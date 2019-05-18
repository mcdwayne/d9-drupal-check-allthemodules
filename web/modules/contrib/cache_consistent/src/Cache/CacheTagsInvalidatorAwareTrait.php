<?php

namespace Drupal\cache_consistent\Cache;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * CacheTagsInvalidatorAware trait.
 *
 * @author Thomas Gielfeldt <thomas@gielfeldt.dk>
 *
 * @codeCoverageIgnore
 *   Too simple to test.
 */
trait CacheTagsInvalidatorAwareTrait {
  /**
   * The invalidator.
   *
   * @var CacheTagsInvalidatorInterface
   */
  protected $invalidator = NULL;

  /**
   * Sets the cache tag invalidator.
   *
   * @param CacheTagsInvalidatorInterface|NULL $invalidator
   *   A CacheTagsInvalidatorInterface instance or NULL.
   */
  public function setInvalidator(CacheTagsInvalidatorInterface $invalidator = NULL) {
    $this->invalidator = $invalidator;
  }

  /**
   * Get the cache tag invalidator.
   *
   * @return CacheTagsInvalidatorInterface|NULL
   *   A CacheTagsInvalidatorInterface instance or NULL.
   */
  public function getInvalidator() {
    return $this->invalidator;
  }

}
