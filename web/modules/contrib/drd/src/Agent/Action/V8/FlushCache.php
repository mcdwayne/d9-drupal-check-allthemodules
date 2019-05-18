<?php

namespace Drupal\drd\Agent\Action\V8;

/**
 * Provides a 'FlushCache' code.
 */
class FlushCache extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    drupal_flush_all_caches();
    return [
      'data' => 'cache flushed',
    ];
  }

}
