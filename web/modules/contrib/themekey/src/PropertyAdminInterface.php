<?php

/**
 * @file
 * Provides Drupal\themekey\PropertyInterface
 */

/* TODO
 * - custom GUI / form elements, p.e. days of week, months
 * - validators (annotations?)
 */

namespace Drupal\themekey;

use Drupal\themekey\Plugin\SingletonPluginInspectionInterface;

/**
 * Defines an interface for ThemeKey property plugins.
 */
interface PropertyAdminInterface extends SingletonPluginInspectionInterface {

  /**
   * Return the list of possible values of a ThemeKey property.
   *
   * @return array
   *  empty if no list possible values
   */
  public function getPossibleValues();

  /**
   * Validates if a value's format matches a ThemeKey property.
   *
   * @return bool
   */
  public function validateFormat($value);


  /**
   * Return the the current values of the ThemeKey property.
   *
   * @return array
   *   special form element or empty.
   */
  public function getFormElement();

}
