<?php

namespace Drupal\token_custom;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a custom token type entity.
 */
interface TokenCustomTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the description of the token type.
   *
   * @return string
   *   The description of the type of this token_custom.
   */
  public function getDescription();

}
