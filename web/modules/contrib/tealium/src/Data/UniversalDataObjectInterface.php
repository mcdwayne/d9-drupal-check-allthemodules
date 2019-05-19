<?php

namespace Drupal\tealium\Data;

/**
 * Interface for the Universal Data Object.
 */
interface UniversalDataObjectInterface {

  /**
   * Sets value for a single data source.
   */
  public function setDataSourceValue($name, $value);

  /**
   * Gets value from a single data source.
   */
  public function getDataSourceValue($name);

  /**
   * Unsets value from a single data source.
   */
  public function unsetDataSourceValue($name);

}
