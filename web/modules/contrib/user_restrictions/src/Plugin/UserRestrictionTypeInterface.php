<?php

namespace Drupal\user_restrictions\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines the interface for UserRestrictionType plugins.
 */
interface UserRestrictionTypeInterface extends ContainerFactoryPluginInterface {

  /**
   * Check if the given data matches the restriction.
   *
   * @param array $data
   *   Data to check.
   *
   * @return bool
   *   TRUE if the value matches one of the restrictions, FALSE otherwise.
   */
  public function matches(array $data);

  /**
   * Get the list of regular expression patterns of the type.
   *
   * @return string[]
   *   Array with regular expression patterns.
   */
  public function getPatterns();

  /**
   * Get the plugin label.
   *
   * @return string
   *   The plugin label.
   */
  public function getLabel();

  /**
   * Get error message displayed to the user.
   *
   * @return string
   *   Error message.
   */
  public function getErrorMessage();

}
