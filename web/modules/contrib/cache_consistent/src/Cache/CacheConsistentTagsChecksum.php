<?php

namespace Drupal\cache_consistent\Cache;

use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\transactionalphp\TransactionalPhp;
use Drupal\transactionalphp\TransactionalPhpAwareTrait;
use Gielfeldt\TransactionalPHP\Operation;

/**
 * Cache tags invalidations checksum implementation that uses the database.
 */
class CacheConsistentTagsChecksum implements CacheTagsChecksumInterface, CacheTagsInvalidatorInterface {

  use TransactionalPhpAwareTrait;

  /**
   * A list of tags that have already been invalidated in this request.
   *
   * Used to prevent the invalidation of the same cache tag multiple times.
   *
   * @var array
   */
  protected $invalidatedTags = array();

  /**
   * CacheConsistentTagsChecksum constructor.
   *
   * @param \Drupal\transactionalphp\TransactionalPhp $transactional_php
   *   The transactional php object.
   *
   * @codeCoverageIgnore
   *   Too difficult to test constructors.
   */
  public function __construct(TransactionalPhp $transactional_php) {
    $this->setTransactionalPhp($transactional_php);
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    $invalidated_tags = &$this->invalidatedTags;
    $operation = (new Operation())
      ->onBuffer(function() use (&$invalidated_tags, $tags) {
        foreach ($tags as $tag) {
          $invalidated_tags[$tag] = isset($invalidated_tags[$tag]) ? $invalidated_tags[$tag] + 1 : 1;
        }
      })
      ->onRemove(function() use (&$invalidated_tags, $tags) {
        foreach ($tags as $tag) {
          $invalidated_tags[$tag]--;
        }
      });
    $this->transactionalPhp->addOperation($operation);
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentChecksum(array $tags) {
    return $this->calculateChecksum($tags);
  }

  /**
   * {@inheritdoc}
   */
  public function isValid($checksum, array $tags) {
    return $checksum == $this->calculateChecksum($tags);
  }

  /**
   * Calculates the current checksum for a given set of tags.
   *
   * @param array $tags
   *   The array of tags to calculate the checksum for.
   *
   * @return int
   *   The calculated checksum.
   */
  protected function calculateChecksum(array $tags) {
    $checksum = 0;
    foreach ($tags as $tag) {
      $checksum += isset($this->invalidatedTags[$tag]) ? $this->invalidatedTags[$tag] : 0;
    }
    return $checksum;
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function reset() {
    $this->invalidatedTags = array();
  }

}
