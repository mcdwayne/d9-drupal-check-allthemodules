<?php

namespace Drupal\prod_check\Plugin;

/**
 * Defines an interface for prod checks
 */
interface ProdCheckInterface {

  /**
   * Initializes the check plugin.
   */
  public function init();

  /**
   * Returns the title of the plugin.
   *
   * @return
   *   The title
   */
  public function title();

  /**
   * Returns the title of the check
   *
   * @return
   *   The category
   */
  public function category();

  /**
   * Returns the extra data of the check.
   *
   * @return
   *   The category
   */
  public function data();

  /**
   * Calculates the state for the check.
   *
   * @return
   *   TRUE if the check passed
   *   FALSE otherwise
   */
  public function state();

  /**
   * Defines the severity of the check.
   *
   * @return
   *   The severity
   */
  public function severity();

  /**
   * Returns the success messages for the check.
   *
   * @return
   *   An associative array containing the following keys
   *     - value: the value of the check
   *     - description: the description of the check
   */
  public function successMessages();

  /**
   * Returns the fail messages for the check
   *
   * @return
   *   An associative array containing the following keys
   *     - value: the value of the check
   *     - description: the description of the check
   */
  public function failMessages();

}
