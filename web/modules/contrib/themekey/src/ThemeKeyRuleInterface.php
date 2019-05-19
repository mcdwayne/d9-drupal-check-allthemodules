<?php

/**
 * @file
 * Contains Drupal\themekey\ThemeKeyRuleInterface.
 */

namespace Drupal\themekey;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a ThemeKeyRule entity.
 */
interface ThemeKeyRuleInterface extends ConfigEntityInterface
{

  /**
   * @return string
   */
  public function property();

  /**
   * @return string
   */
  public function key();

  /**
   * @return string
   */
  public function operator();

  /**
   * @return string
   */
  public function value();

  /**
   * @return string
   */
  public function theme();

  /**
   * @return string
   */
  public function comment();

  /**
   * @return string
   */
  public function toString();

}
