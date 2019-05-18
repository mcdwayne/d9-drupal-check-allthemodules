<?php

namespace Drupal\evergreen;

/**
 * Defines the ExpiryOptionsInterface.
 */
interface ExpiryProviderInterface {

  /**
   * Get the form element for this interface.
   *
   * This should include the value as the selected option.
   */
  public function getFormElement($value);

  /**
   * Process the selected value.
   */
  public function processValue($value);

}
