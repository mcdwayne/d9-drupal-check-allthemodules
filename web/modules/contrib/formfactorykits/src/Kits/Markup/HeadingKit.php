<?php

namespace Drupal\formfactorykits\Kits\Markup;

/**
 * Class HeadingKit
 *
 * @package Drupal\formfactorykits\Kits
 */
class HeadingKit extends MarkupKit {
  const ID = 'heading';
  const KEY_NUMBER = 'number';

  /**
   * @inheritdoc
   */
  public function getArray() {
    $artifact = parent::getArray();
    $markup = $this->getMarkup();
    if ($markup) {
      $artifact['#' . self::MARKUP_KEY] = $markup;
    }
    return $artifact;
  }

  /**
   * @inheritdoc
   */
  public function getMarkup($default = NULL) {
    $value = $this->getValue();
    if (empty($value)) {
      return $default;
    }
    $number = $this->getNumber();
    return (string) vsprintf('<h%d>%s</h%d>', [
      $number,
      $value,
      $number,
    ]);
  }

  /**
   * @param mixed $default
   *
   * @return mixed
   */
  public function getValue($default = NULL) {
    return $this->getContext(self::VALUE_KEY, $default);
  }

  /**
   * @inheritdoc
   */
  public function setValue($value) {
    return $this->setContext(self::VALUE_KEY, $value);
  }

  /**
   * @param int $number
   * @return static
   */
  public function setNumber($number) {
    return $this->setContext(static::KEY_NUMBER, $number);
  }

  /**
   * @param int $default
   * @return int
   */
  public function getNumber($default = 1) {
    $number = $this->getContext(static::KEY_NUMBER);
    if (NULL === $number) {
      return $default;
    }
    return $number;
  }
}
