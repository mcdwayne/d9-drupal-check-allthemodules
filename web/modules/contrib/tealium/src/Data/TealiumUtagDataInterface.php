<?php

namespace Drupal\tealium\Data;

/**
 * Class Interface for the Tealium Universal Data Object (utag_data).
 */
interface TealiumUtagDataInterface extends UniversalDataObjectInterface {

  /**
   * Gets all javascript variables set.
   */
  public function getJavascriptVariables();

}
