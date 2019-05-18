<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data\Payment;

use Drupal\commerce_klarna_payments\Klarna\Data\ObjectInterface;

/**
 * An interface to describe options type.
 */
interface OptionsInterface extends ObjectInterface {

  /**
   * Sets the button color.
   *
   * CSS hex color, e.g. "#FF9900".
   *
   * @param string $color
   *   The color.
   *
   * @return $this
   *   The self.
   */
  public function setButtonColor(string $color) : OptionsInterface;

  /**
   * Sets the button text color.
   *
   * CSS hex color, e.g. "#FF9900".
   *
   * @param string $color
   *   The color.
   *
   * @return $this
   *   The self.
   */
  public function setButtonTextColor(string $color) : OptionsInterface;

  /**
   * Sets the checkbox color.
   *
   * CSS hex color, e.g. "#FF9900".
   *
   * @param string $color
   *   The color.
   *
   * @return $this
   *   The self.
   */
  public function setCheckBoxColor(string $color) : OptionsInterface;

  /**
   * Sets the checkbox checkmark color.
   *
   * CSS hex color, e.g. "#FF9900".
   *
   * @param string $color
   *   The color.
   *
   * @return $this
   *   The self.
   */
  public function setCheckBoxCheckMarkColor(string $color) : OptionsInterface;

  /**
   * Sets the header color.
   *
   * CSS hex color, e.g. "#FF9900".
   *
   * @param string $color
   *   The color.
   *
   * @return $this
   *   The self.
   */
  public function setHeaderColor(string $color) : OptionsInterface;

  /**
   * Sets the link color.
   *
   * CSS hex color, e.g. "#FF9900".
   *
   * @param string $color
   *   The color.
   *
   * @return $this
   *   The self.
   */
  public function setLinkColor(string $color) : OptionsInterface;

  /**
   * Sets the border color.
   *
   * CSS hex color, e.g. "#FF9900".
   *
   * @param string $color
   *   The color.
   *
   * @return $this
   *   The self.
   */
  public function setBorderColor(string $color) : OptionsInterface;

  /**
   * Sets the selected border color.
   *
   * CSS hex color, e.g. "#FF9900".
   *
   * @param string $color
   *   The color.
   *
   * @return $this
   *   The self.
   */
  public function setSelectedBorderColor(string $color) : OptionsInterface;

  /**
   * Sets the text color.
   *
   * CSS hex color, e.g. "#FF9900".
   *
   * @param string $color
   *   The color.
   *
   * @return $this
   *   The self.
   */
  public function setTextColor(string $color) : OptionsInterface;

  /**
   * Sets the details color.
   *
   * CSS hex color, e.g. "#FF9900".
   *
   * @param string $color
   *   The color.
   *
   * @return $this
   *   The self.
   */
  public function setDetailsColor(string $color) : OptionsInterface;

  /**
   * Sets the secondary text color.
   *
   * CSS hex color, e.g. "#FF9900".
   *
   * @param string $color
   *   The color.
   *
   * @return $this
   *   The self.
   */
  public function setSecondaryTextColor(string $color) : OptionsInterface;

  /**
   * Sets the border radius.
   *
   * @param string $radius
   *   The border radius.
   *
   * @return $this
   *   The self.
   */
  public function setBorderRadius(string $radius) : OptionsInterface;

}
