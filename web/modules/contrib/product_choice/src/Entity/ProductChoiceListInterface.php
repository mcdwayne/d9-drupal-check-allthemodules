<?php

namespace Drupal\product_choice\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Product choice list entities.
 */
interface ProductChoiceListInterface extends ConfigEntityInterface {

  /**
   * Gets the product choice list's label.
   *
   * @return string
   *   The product choice list label.
   */
  public function getLabel();

  /**
   * Sets the product choice list's label.
   *
   * @param string $label
   *   The product choice list label.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Gets the product choice list's description.
   *
   * @return string
   *   The product choice list description.
   */
  public function getDescription();

  /**
   * Sets the product choice list's description.
   *
   * @param string $description
   *   The product choice list description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Gets the product choice list's help text.
   *
   * @return string
   *   The product choice list help text.
   */
  public function getHelpText();

  /**
   * Sets the product choice list's help text.
   *
   * @param string $help_text
   *   The product choice list help text.
   *
   * @return $this
   */
  public function setHelpText($help_text);

  /**
   * Returns a list of allowed formats for the product choice list.
   *
   * @return array
   *   Allowed formats for the product choice list.
   */
  public function getAllowedFormats();

  /**
   * Checks if the product choice list has an allowed format.
   *
   * @param string $allowed_format
   *   The allowed format to check for.
   *
   * @return bool
   *   TRUE if the product choice list has the allowed format, FALSE if not.
   */
  public function hasAllowedFormat($allowed_format);

  /**
   * Add an allowed format to the product choice list.
   *
   * @param string $allowed_format
   *   The allowed format to add.
   *
   * @return $this
   */
  public function addAllowedFormat($allowed_format);

  /**
   * Removes an allowed format from the product choice list.
   *
   * @param string $allowed_format
   *   The allowed format to remove.
   *
   * @return $this
   */
  public function removeAllowedFormat($allowed_format);

}
