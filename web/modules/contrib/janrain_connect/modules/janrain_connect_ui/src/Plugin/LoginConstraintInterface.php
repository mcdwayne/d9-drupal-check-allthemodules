<?php

namespace Drupal\janrain_connect_ui\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * An interface to define the expected operations of a login constraint.
 */
interface LoginConstraintInterface extends PluginInspectionInterface {

  /**
   * Returns a true/false status if the login meets the constraint.
   *
   * @param array $result
   *   Result returned by Janrain API.
   *
   * @return bool
   *   Whether or not the login meets the constraint in the plugin.
   */
  public function validate(array $result);

  /**
   * Returns a translated error message for the constraint.
   *
   * @return string
   *   Error message if the constraint fails.
   */
  public function getErrorMessage();

}
