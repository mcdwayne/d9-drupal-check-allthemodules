<?php

namespace Drupal\amp_validator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Validator plugins.
 */
interface AmpValidatorPluginInterface extends PluginInspectionInterface {

  /**
   * Get errors after running AMP validation.
   */
  public function getErrors();

  /**
   * Returns AMP valid status.
   */
  public function isValid();

  /**
   * Set data object for AMP validation.
   */
  public function setData($data);

  /**
   * Run AMP validation.
   */
  public function validate();

}
