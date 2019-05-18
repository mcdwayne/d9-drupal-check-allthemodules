<?php

namespace Drupal\drd\Agent\Action\V8;

/**
 * Provides a 'Ping' code.
 */
class Ping extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return [
      'data' => 'pong',
    ];
  }

}
