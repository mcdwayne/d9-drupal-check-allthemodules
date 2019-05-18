<?php

namespace Drupal\token_custom;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a custom token entity.
 */
interface TokenCustomInterface extends ContentEntityInterface {

  /**
   * Get description.
   *
   * @return string
   *   Description
   */
  public function getDescription();

  /**
   * Get unformatted content.
   *
   * @return string
   *   Content.
   */
  public function getRawContent();

  /**
   * Get formatted content.
   *
   * @return string
   *   Content.
   */
  public function getFormattedContent();

  /**
   * Get text format.
   *
   * @return string
   *   Text format.
   */
  public function getFormat();

}
