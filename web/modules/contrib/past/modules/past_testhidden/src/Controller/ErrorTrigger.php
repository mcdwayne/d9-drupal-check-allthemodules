<?php

namespace Drupal\past_testhidden\Controller;

/**
 * Controller to trigger some errors for use with Past testing.
 */
class ErrorTrigger {

  /**
   * Triggers an error of the given type.
   *
   * @param string $type
   *   The error type.
   *
   * @return array
   *   An empty array.
   */
  public function trigger($type) {
    module_load_include('inc', 'past_testhidden', '/errors/past.' . $type);
    return ['#markup' => 'hello, world'];
  }
}
