<?php

namespace Drupal\drd\Agent\Action\V6;

/**
 * Provides a 'FlushCache' code.
 */
class FlushCache extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    drupal_flush_all_caches();
    return array(
      'data' => 'cache flushed',
    );
  }

}
