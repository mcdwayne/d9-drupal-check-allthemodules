<?php

namespace Drupal\drd\Agent\Action\V6;

/**
 * Provides a 'Ping' code.
 */
class Ping extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return array(
      'data' => 'pong',
    );
  }

}
