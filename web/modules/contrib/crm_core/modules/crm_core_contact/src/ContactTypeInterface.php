<?php

namespace Drupal\crm_core_contact;

/**
 * Defines methods for CRM Contact Type entities.
 */
interface ContactTypeInterface {

  /**
   * Returns the human readable name of any or all contact types.
   *
   * @return array
   *   An array containing all human readable names keyed on the machine type.
   */
  public static function getNames();

}
