<?php

namespace Drupal\living_style_guide\Generator;

/**
 * Interface ValueInterface.
 *
 * @package Drupal\living_style_guide\Generator
 */
interface ValueInterface {

  /**
   * Gets a value for the specified field type.
   *
   * @param string $fieldType
   *   The field type.
   *
   * @return mixed|null
   *   A value for the given field type or NULL for unknown field types.
   */
  public function getValue($fieldType);

  /**
   * Gets a boolean.
   *
   * @return bool
   *   A boolean.
   */
  public function getBoolean();

  /**
   * Gets a date time string.
   *
   * @return string
   *   A formatted date time string.
   */
  public function getDateTime();

  /**
   * Gets a timestamp.
   *
   * @return int
   *   A timestamp.
   */
  public function getTimestamp();

  /**
   * Gets an email.
   *
   * @return string
   *   An email.
   */
  public function getEmail();

  /**
   * Gets a float.
   *
   * @return float
   *   A float.
   */
  public function getFloat();

  /**
   * Gets an integer.
   *
   * @return int
   *   An integer.
   */
  public function getInteger();

  /**
   * Gets a short piece of text.
   *
   * @return string
   *   A short piece of text.
   */
  public function getTextShort();

  /**
   * Gets a long piece of text.
   *
   * @return string
   *   A long piece of text.
   */
  public function getTextLong();

  /**
   * Gets an image file.
   *
   * @return \Drupal\file\Entity\File
   *   An image file.
   */
  public function getFieldImage();

}
