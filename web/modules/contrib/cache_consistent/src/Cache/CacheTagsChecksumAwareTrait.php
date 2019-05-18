<?php

namespace Drupal\cache_consistent\Cache;

use Drupal\Core\Cache\CacheTagsChecksumInterface;

/**
 * CacheTagsChecksumAware trait.
 *
 * @author Thomas Gielfeldt <thomas@gielfeldt.dk>
 *
 * @codeCoverageIgnore
 *   Too simple to test.
 */
trait CacheTagsChecksumAwareTrait {
  /**
   * The checksum provider.
   *
   * @var CacheTagsChecksumInterface
   */
  protected $checksumProvider = NULL;

  /**
   * Sets the checksum provider.
   *
   * @param CacheTagsChecksumInterface|NULL $checksum_provider
   *   A CacheTagsChecksumInterface instance or NULL.
   */
  public function setChecksumProvider(CacheTagsChecksumInterface $checksum_provider = NULL) {
    $this->checksumProvider = $checksum_provider;
  }

  /**
   * Get the checksum provider.
   *
   * @return CacheTagsChecksumInterface|NULL
   *   A CacheTagsChecksumInterface instance or NULL.
   */
  public function getChecksumProvider() {
    return $this->checksumProvider;
  }

}
