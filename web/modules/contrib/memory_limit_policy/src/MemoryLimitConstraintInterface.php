<?php

namespace Drupal\memory_limit_policy;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * An interface to define the expected operations of a memory limit constraint.
 */
interface MemoryLimitConstraintInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns a translated string for the constraint title.
   *
   * @return string
   *   Title of the constraint.
   */
  public function getTitle();

  /**
   * Returns a translated description for the constraint description.
   *
   * @return string
   *   Description of the constraint.
   */
  public function getDescription();

  /**
   * Returns a human-readable summary of the constraint.
   *
   * @return string
   *   Summary of the constraint behaviors or restriction.
   */
  public function getSummary();

  /**
   * Evaluate the constraint to check if it applies.
   *
   * @return bool
   *   TRUE if the constraint applies, FALSE otherwise.
   */
  public function evaluate();

}
