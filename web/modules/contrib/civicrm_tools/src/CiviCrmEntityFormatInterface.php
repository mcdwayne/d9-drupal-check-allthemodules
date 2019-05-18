<?php

namespace Drupal\civicrm_tools;

/**
 * Interface CiviCrmEntityFormatInterface.
 *
 * @todo find another design pattern (decorator?)
 * instead of multiple interfaces in each implementation.
 */
interface CiviCrmEntityFormatInterface {

  /**
   * Formats a list of entities as a key - label.
   *
   * Usable for form select elements.
   *
   * @param array $values
   *   Original list of entities.
   *
   * @return array
   *   List of formatted entities.
   */
  public function labelFormat(array $values);

}
