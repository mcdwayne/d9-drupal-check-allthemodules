<?php

namespace Drupal\cache_consistent\Cache;

use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Site\Settings;
use Drupal\transactionalphp\TransactionalPhpAwareTrait;
use Gielfeldt\TransactionalPHP\Operation;

/**
 * Class CacheConsistentTagsInvalidator.
 *
 * @package Drupal\cache_consistent\Cache
 */
class CacheConsistentTagsInvalidator extends CacheTagsInvalidator {

  use TransactionalPhpAwareTrait;

  /**
   * Settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Holds an array of consistent cache tags invalidators.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface[]
   */

  protected $consistentInvalidators = [];

  /**
   * CacheConsistentTagsInvalidator constructor.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   Drupal settings.
   *
   * @codeCoverageIgnore
   *   Too difficult to test constructors.
   */
  public function __construct(Settings $settings) {
    $this->settings = $settings->get('cache');
  }

  /**
   * Adds a consistent cache tags invalidator.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $invalidator
   *   A cache invalidator.
   */
  public function addConsistentInvalidator(CacheTagsInvalidatorInterface $invalidator) {
    $this->consistentInvalidators[] = $invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    // No test when disabled.
    // @codeCoverageIgnoreStart
    if (!empty($this->settings['consistent'])) {
      parent::invalidateTags($tags);
      return;
    }
    // @codeCoverageIgnoreEnd

    assert('Drupal\Component\Assertion\Inspector::assertAllStrings($tags)', 'Cache tags must be strings.');

    // Notify all consistent cache tags invalidators.
    foreach ($this->consistentInvalidators as $invalidator) {
      $invalidator->invalidateTags($tags);
    }

    // Notify all non-consistent cache tags invalidators.
    foreach ($this->invalidators as $invalidator) {
      $operation = (new Operation())
        ->onCommit(function () use ($invalidator, $tags) {
          $invalidator->invalidateTags($tags);
        });
      $this->transactionalPhp->addOperation($operation);
    }

    // Additionally, notify each cache bin if it implements the service.
    // Cache invalidations via backends are always assumed to be consistent
    // since otherwise it would be wrapped by the cache consistent backend.
    foreach ($this->getInvalidatorCacheBins() as $bin) {
      $bin->invalidateTags($tags);
    }
  }

}
