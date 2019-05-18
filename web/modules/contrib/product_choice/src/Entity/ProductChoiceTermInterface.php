<?php

namespace Drupal\product_choice\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Product choice term entities.
 *
 * @ingroup product_choice
 */
interface ProductChoiceTermInterface extends ContentEntityInterface {

  /**
   * Gets the Product choice list.
   *
   * @return string
   *   The Product choice list.
   */
  public function getList();

  /**
   * Gets the Product choice term label.
   *
   * @return string
   *   Label of the Product choice term.
   */
  public function getLabel();

  /**
   * Sets the Product choice term label.
   *
   * @param string $label
   *   The Product choice term label.
   *
   * @return \Drupal\product_choice\Entity\ProductChoiceTermInterface
   *   The called Product choice term entity.
   */
  public function setLabel($label);

  /**
   * Gets the Product choice term shortened label.
   *
   * @return string
   *   Shortened label of the Product choice term.
   */
  public function getShortened();

  /**
   * Gets the Product choice term formatted label text.
   *
   * @return string
   *   Formatted label text of the Product choice term.
   */
  public function getFormattedText();

  /**
   * Gets the Product choice term formatted label format option.
   *
   * @return string
   *   Format option for the formatted label of the Product choice term.
   */
  public function getFormattedFormat();

}
