<?php

namespace Drupal\drd\Agent\Action\V7;

/**
 * Provides a 'FlushCache' code.
 */
class FlushCache extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    drupal_flush_all_caches();
    registry_rebuild();
    return array(
      'data' => 'cache flushed',
    );
  }

}
