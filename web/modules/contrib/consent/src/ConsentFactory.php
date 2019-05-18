<?php

namespace Drupal\consent;

/**
 * Class ConsentFactory.
 */
class ConsentFactory implements ConsentFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    return new Consent($values);
  }

}
