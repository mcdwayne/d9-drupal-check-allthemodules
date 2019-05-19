<?php

/**
 * @file
 * Provides Drupal\themekey\OperatorInterface
 */

namespace Drupal\themekey;

use Drupal\themekey\Plugin\SingletonPluginInspectionInterface;

/**
 * Defines an interface for ThemeKey operator plugins.
 */
interface OperatorInterface extends SingletonPluginInspectionInterface {

  /**
   * Return the name of the ThemeKey operator.
   *
   * @return string
   */
  public function getName();

  /**
   * Return the Description of the ThemeKey operator.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Return the Description of the ThemeKey operator.
   *
   * @return bool
   *  ($value1 OPERATOR $value2)
   */
  public function evaluate($value1, $value2);

  /**
   * Validate.
   */
  public function validate(\Drupal\themekey\PropertyAdminInterface $propertyAdmin, $value, \Drupal\Core\Form\FormStateInterface $form_state);

}
