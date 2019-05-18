<?php

namespace Drupal\apitools\Utility;

use Symfony\Component\HttpFoundation\ParameterBag as SymfonyParameterBag;
use Drupal\Component\Utility\UrlHelper;

class ParameterBag extends SymfonyParameterBag {

  /**
   * Helper function to sort by key.
   */
  public function sort() {
    $iterator = $this->getIterator();
    $iterator->ksort();
    $this->replace($iterator->getArrayCopy());
    return $this;
  }

  /**
   * Export current params as a query string.
   *
   * @return string
   */
  public function query() {
    return UrlHelper::buildQuery($this->sort()->all());
  }
}
