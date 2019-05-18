<?php

namespace Drupal\drd\Agent\Action\V7;

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
