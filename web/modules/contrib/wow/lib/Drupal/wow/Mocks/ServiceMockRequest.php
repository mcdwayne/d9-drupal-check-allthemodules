<?php

namespace Drupal\wow\Mocks;

use WoW\Core\Request;
use WoW\Core\ServiceInterface;

/**
 * Mocks the request method.
 */
class ServiceMockRequest implements ServiceInterface {
  public $path;
  public $query;
  public $headers;

  public function newRequest($path) {}
  public function getLocale($language) {
    return $language == 'test' ? 'Test Locale' : NULL;
  }
  public function getLocales() {}
  public function getRegion() {}
  public function request($path, array $query = array(), array $headers = array()) {
    $this->path = $path;
    $this->query = $query;
    $this->headers = $headers;
    return NULL;
  }
}
