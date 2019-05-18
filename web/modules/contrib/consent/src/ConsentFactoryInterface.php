<?php

namespace Drupal\consent;

/**
 * Interface ConsentFactoryInterface.
 */
interface ConsentFactoryInterface {

  /**
   * Creates a new instance for user consent information.
   *
   * @param array $values
   *   (Optional) Any known values.
   *
   * @return \Drupal\consent\ConsentInterface
   *   A new instance of a user consent.
   */
  public function create(array $values = []);

}
