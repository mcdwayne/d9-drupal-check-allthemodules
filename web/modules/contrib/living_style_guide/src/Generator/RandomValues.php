<?php

namespace Drupal\living_style_guide\Generator;

use Drupal\Component\Utility\Random;

/**
 * Class RandomValues.
 *
 * @package Drupal\living_style_guide
 */
class RandomValues implements ValueInterface {

  /**
   * Random utility.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected $randomUtility;

  /**
   * {@inheritdoc}
   */
  public function getValue($fieldType) {
    switch ($fieldType) {
      case 'boolean':
        return $this->getBoolean();

      case 'datetime':
        return $this->getDateTime();

      case 'timestamp':
        return $this->getTimestamp();

      case 'email':
        return $this->getEmail();

      case 'decimal':
      case 'float':
        return $this->getFloat();

      case 'integer':
        return $this->getInteger();

      case 'image':
        return $this->getFieldImage();

      case 'text':
        return $this->getTextLong(1, 3);

      case 'text_long':
      case 'text_with_summary':
        return $this->getTextLong();

      case 'string':
        return $this->getTextShort();

      case 'string_long':
        return $this->getTextShort(3, 5);
    }

    return NULL;
  }

  /**
   * Generates a random boolean.
   *
   * @return bool
   *   A randomly set boolean.
   */
  public function getBoolean() {
    return rand(0, 1) == 1;
  }

  /**
   * Sets the random utility if it's not set yet and always returns it.
   *
   * @return \Drupal\Component\Utility\Random
   *   The random utility.
   */
  private function getRandomUtility() {
    if (!isset($this->randomUtility)) {
      $this->randomUtility = new Random();
    }

    return $this->randomUtility;
  }

  /**
   * Gets a random date time.
   *
   * @return false|string
   *   A formatted date time string.
   */
  public function getDateTime() {
    $timestamp = rand(1, time());
    $randomDate = date("Y-m-d\TH:i:s", $timestamp);

    return $randomDate;
  }

  /**
   * Gets a random timestamp.
   *
   * @return int
   *   A random timestamp.
   */
  public function getTimestamp() {
    return rand(1, time());
  }

  /**
   * Generates a random email address.
   *
   * @return string
   *   Randomly generated email address.
   */
  public function getEmail() {
    $randomUtility = $this->getRandomUtility();

    $email = $randomUtility->name(rand(3, 12)) . '@';
    $email .= $randomUtility->name(rand(3, 20)) . '.';
    $email .= $randomUtility->name(rand(2, 3));

    return $email;
  }

  /**
   * Gets a random float.
   *
   * @return float
   *   A random float.
   */
  public function getFloat() {
    return rand(-10000, 10000) / 10;
  }

  /**
   * Gets a random integer.
   *
   * @return int
   *   A random integer.
   */
  public function getInteger() {
    return rand(-1000, 1000);
  }

  /**
   * Generates a random short text.
   *
   * @param int $minWords
   *   Minimum amount of words.
   * @param int $maxMinWords
   *   Maximum amount of minimum amount of words.
   *
   * @return string
   *   A random short piece of text.
   */
  public function getTextShort($minWords = 1, $maxMinWords = 3) {
    return $this->getRandomUtility()->sentences(rand($minWords, $maxMinWords));
  }

  /**
   * Generates a random long text.
   *
   * @param int $minParagraphs
   *   Minimum amount of paragraphs.
   * @param int $maxParagraphs
   *   Maximum amount of paragraphs.
   *
   * @return string
   *   A random long piece of text.
   */
  public function getTextLong($minParagraphs = 1, $maxParagraphs = 10) {
    return $this->getRandomUtility()->paragraphs(rand($minParagraphs, $maxParagraphs));
  }

  /**
   * Generates a random image.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Randomly generated image file.
   */
  public function getFieldImage() {
    $width = rand(50, 2000);
    $height = rand(50, 1000);

    $uri = 'https://picsum.photos/' . $width . '/' . $height . '.png';

    $file = \Drupal::entityTypeManager()->getStorage('file')->create(
      [
        'type' => 'image',
        'uri' => $uri,
      ]
    );

    return $file;
  }
}
