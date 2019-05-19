<?php

namespace Drupal\token_default\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Default token entities.
 */
interface TokenDefaultTokenInterface extends ConfigEntityInterface {

  /**
   * Get the tokenized pattern for the token default.
   *
   * @return string
   *   The token pattern.
   */
  public function getPattern();

  /**
   * Set the tokenized pattern for the token default.
   *
   * @param string $pattern
   *   The token pattern.
   *
   * @return $this
   */
  public function setPattern($pattern);

  /**
   * Get the tokenized replacement for the token default.
   *
   * @return string
   *   The token replacement string or pattern.
   */
  public function getReplacement();

  /**
   * Set the tokenized replacement for the token default.
   *
   * @param string $replacement
   *   The token replacement string or pattern.
   *
   * @return $this
   */
  public function setReplacement($replacement);

  /**
   * Get the bundle for the token default.
   *
   * @return string
   *   The bundle this replacement applies to.
   */
  public function getBundle();

  /**
   * Set the bundle for the token default.
   *
   * @param string $bundle
   *   The bundle this replacement applies to.
   *
   * @return $this
   */
  public function setBundle($bundle);

}
