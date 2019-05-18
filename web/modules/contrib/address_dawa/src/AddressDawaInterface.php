<?php

namespace Drupal\address_dawa;

/**
 * Defines the interface for DAWA address.
 */
interface AddressDawaInterface {

  /**
   * Fetch address from DAWA service.
   *
   * @param array $options
   *   Array of search query parameters.
   * @param string $address_type
   *   Address type. If nothing is chosen, DAWA autocomplete query will be made.
   *
   * @return array
   *   Possible matches.
   */
  public function fetchAddress(array $options, $address_type = '');

}
