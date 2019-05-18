<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Request\Payment;

use Drupal\commerce_klarna_payments\Klarna\Data\Payment\OptionsInterface;
use Drupal\commerce_klarna_payments\Klarna\ObjectNormalizer;

/**
 * Value object for options.
 */
class Options implements OptionsInterface {

  use ObjectNormalizer;

  protected $data = [
    'color_button' => NULL,
    'color_button_text' => NULL,
    'color_checkbox' => NULL,
    'color_checkbox_checkmark' => NULL,
    'color_header' => NULL,
    'color_link' => NULL,
    'color_border' => NULL,
    'color_border_selected' => NULL,
    'color_text' => NULL,
    'color_details' => NULL,
    'color_text_secondary' => NULL,
    'radius_border' => NULL,
  ];

  /**
   * Constructs a new instance.
   *
   * @param array $data
   *   The data.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\Payment\OptionsInterface
   *   The self.
   */
  public static function create(array $data) : OptionsInterface {
    $instance = (new self())
      ->setButtonColor($data['color_button'] ?? '')
      ->setButtonTextColor($data['color_button_text'] ?? '')
      ->setCheckBoxColor($data['color_checkbox'] ?? '')
      ->setCheckBoxCheckMarkColor($data['color_checkbox_checkmark'] ?? '')
      ->setHeaderColor($data['color_header'] ?? '')
      ->setLinkColor($data['color_link'] ?? '')
      ->setBorderColor($data['color_border'] ?? '')
      ->setSelectedBorderColor($data['color_border_selected'] ?? '')
      ->setTextColor($data['color_text'] ?? '')
      ->setDetailsColor($data['color_details'] ?? '')
      ->setSecondaryTextColor($data['color_text_secondary'] ?? '')
      ->setBorderRadius($data['radius_border'] ?? '');

    return $instance;
  }

  /**
   * Gets the available options.
   *
   * @return array
   *   The options.
   */
  public function getAvailableOptions() : array {
    return array_keys($this->data);
  }

  /**
   * Asserts that given color is valid hexadecimal color.
   *
   * @param string $color
   *   The color code.
   */
  public function assertColor(string $color) : void {
    if (!ctype_xdigit($color) || strlen($color) !== 6) {
      throw new \InvalidArgumentException('Color must be valid hexadecimal and exactly 6 characters long.');
    }
  }

  /**
   * Sets the color.
   *
   * You can unset colors with empty string.
   *
   * @param string $color
   *   The color.
   * @param string $type
   *   The type.
   *
   * @return $this
   *   The self.
   */
  protected function setColor(string $color, string $type) : OptionsInterface {
    // Allow empty values.
    $value = '';

    if (strlen($color) > 0) {
      // Remove # if added.
      $color = ltrim($color, '#');
      $this->assertColor($color);

      // Add # back.
      $value = sprintf('#%s', $color);
    }
    $this->data[$type] = $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setButtonColor(string $color) : OptionsInterface {
    return $this->setColor($color, 'color_button');
  }

  /**
   * {@inheritdoc}
   */
  public function setButtonTextColor(string $color) : OptionsInterface {
    return $this->setColor($color, 'color_button_text');
  }

  /**
   * {@inheritdoc}
   */
  public function setCheckBoxColor(string $color) : OptionsInterface {
    return $this->setColor($color, 'color_checkbox');
  }

  /**
   * {@inheritdoc}
   */
  public function setCheckBoxCheckMarkColor(string $color) : OptionsInterface {
    return $this->setColor($color, 'color_checkbox_checkmark');
  }

  /**
   * {@inheritdoc}
   */
  public function setHeaderColor(string $color) : OptionsInterface {
    return $this->setColor($color, 'color_header');
  }

  /**
   * {@inheritdoc}
   */
  public function setLinkColor(string $color) : OptionsInterface {
    return $this->setColor($color, 'color_link');
  }

  /**
   * {@inheritdoc}
   */
  public function setBorderColor(string $color) : OptionsInterface {
    return $this->setColor($color, 'color_border');
  }

  /**
   * {@inheritdoc}
   */
  public function setSelectedBorderColor(string $color) : OptionsInterface {
    return $this->setColor($color, 'color_border_selected');
  }

  /**
   * {@inheritdoc}
   */
  public function setTextColor(string $color) : OptionsInterface {
    return $this->setColor($color, 'color_text');
  }

  /**
   * {@inheritdoc}
   */
  public function setDetailsColor(string $color) : OptionsInterface {
    return $this->setColor($color, 'color_details');
  }

  /**
   * {@inheritdoc}
   */
  public function setSecondaryTextColor(string $color) : OptionsInterface {
    return $this->setColor($color, 'color_text_secondary');
  }

  /**
   * {@inheritdoc}
   */
  public function setBorderRadius(string $radius) : OptionsInterface {
    $this->data['radius_border'] = $radius;
    return $this;
  }

}
