<?php

namespace Drupal\custom_tokens\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * An interface for the token entity.
 */
interface TokenEntityInterface extends ConfigEntityInterface {

  /**
   * Get the name of the token.
   *
   * @return string
   *   The token name.
   */
  public function getTokenName();

  /**
   * Get the token value.
   *
   * @return string
   *   The token value.
   */
  public function getTokenValue();

}
