<?php

/**
 * @file
 * Contains Drupal\redirect_deleted_entities\RedirectTypeInterface
 */

namespace Drupal\redirect_deleted_entities;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for pathauto alias types.
 */
interface RedirectTypeInterface extends ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Get the label.
   *
   * @return string
   *   The label.
   */
  public function getLabel();

  /**
   * Get the pattern description.
   *
   * @return string
   *   The pattern description.
   */
  public function getPatternDescription();

  /**
   * Get the patterns.
   *
   * @return string[]
   *   The array of patterns.
   */
  public function getPatterns();

  /**
   * Get the token types.
   *
   * @return string[]
   *   The token types.
   */
  public function getTokenTypes();

}
