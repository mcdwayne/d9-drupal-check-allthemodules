<?php

namespace Drupal\sourcepoint\Api\Endpoint;

use Drupal\sourcepoint\Api\AbstractEndpoint;

/**
 * Class Bootstrap.
 *
 * @package Drupal\sourcepoint\Api\Endpoint
 */
class Bootstrap extends AbstractEndpoint {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'bootstrap';
  }

}
