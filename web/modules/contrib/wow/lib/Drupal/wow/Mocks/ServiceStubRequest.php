<?php

namespace Drupal\wow\Mocks;

use WoW\Core\ServiceInterface;
use WoW\Core\Service\Service;

/**
 * Service Stub.
 *
 * Request method does not do anything.
 */
class ServiceStubRequest extends Service implements ServiceInterface {

  public function __construct() {}
  public function request($path, array $query = array(), array $headers = array()) {}
}
