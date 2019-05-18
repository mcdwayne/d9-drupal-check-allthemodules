<?php

namespace Drupal\contacts_dbs\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a dbs workforce entity.
 */
interface DBSWorkforceInterface extends ConfigEntityInterface {

  /**
   * Returns the number of years a workforce is valid for.
   *
   * @return int
   *   Number of years.
   */
  public function getValidity();

  /**
   * Sets the number of years a workforce is valid for.
   *
   * @param int $valid
   *   Number of years.
   *
   * @return $this
   */
  public function setValidity(int $valid);

  /**
   * Returns the list of workforces that can be used as alternatives.
   *
   * @return string[]
   *   List of workforce entity ids.
   */
  public function getAlternatives();

}
