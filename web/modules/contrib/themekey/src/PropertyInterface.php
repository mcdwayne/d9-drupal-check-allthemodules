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
interface PropertyInterface extends SingletonPluginInspectionInterface {

  /**
   * Return the name of the ThemeKey property.
   *
   * @return string
   */
  public function getName();

  /**
   * Return the Description of the ThemeKey property.
   *
   * @return string
   */
  public function getDescription();


  /**
   * Return the the current values of the ThemeKey property.
   *
   * @return array
   *   array of system:query_param values
   */
  public function getValues();

}
