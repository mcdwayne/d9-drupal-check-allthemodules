<?php

namespace Drupal\quick_code;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface QuickCodeTypeInterface extends ConfigEntityInterface {

  /**
   * Determines whether the quick code type is locked.
   *
   * @return string|false
   *   The module name that locks the type or FALSE.
   */
  public function isLocked();

  /**
   * Returns the quick code description.
   *
   * @return string
   *   The quick code type description.
   */
  public function getDescription();

  /**
   * Gets the code.
   *
   * @return bool
   *   The code.
   */
  public function getCode();

  /**
   * Gets the encoding rules.
   *
   * @return string
   *  The encoding rules.
   */
  public function getEncodingRules();

  /**
   * Gets the hierarchy.
   *
   * @return bool
   *   The hierarchy.
   */
  public function getHierarchy();

}
