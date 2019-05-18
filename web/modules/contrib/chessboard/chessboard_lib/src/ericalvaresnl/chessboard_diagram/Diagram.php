<?php

namespace Drupal\chessboard_lib\ericalvaresnl\chessboard_diagram;

/**
 * Defines a Chessboard Images Diagram.
 *
 * @internal
 */
class Diagram {

  /**
   * The border value.
   *
   * @var int
   */
  protected $border;

  /**
   * The language code.
   *
   * @var string
   */
  protected $languageCode;

  /**
   * The square color first value.
   *
   * @var array
   */
  protected $squareColorFirst;

  /**
   * The placement value.
   *
   * @var string
   */
  protected $value;

  /**
   * Number of files.
   *
   * @var int
   */
  protected $fileMax;

  /**
   * Constructs a diagram object.
   *
   * @param string $value
   *   Placement represented as a PHP string.
   * @param int $fileMax
   *   Number of files.
   */
  public function __construct($value, $file_max) {
    $this->value = $value;
    $this->fileMax = $file_max;
  }

  /**
   * {@inheritdoc}
   */
  public function getSize() {
    return strlen($this->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getCode($fen_rank, $fen_file) {
    $index = $fen_rank * $this->getFileMax() + $fen_file;
    if (isset($this->value[$index])) {
      return $this->value[$index];
    }
    else {
      throw new \RuntimeException('Index invalid or out of range');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFileMax() {
    return $this->fileMax;
  }

  public function getBorder() {
    return $this->border;
  }

  public function getLanguageCode() {
    return $this->languageCode;
  }

  public function getSquareColorFirst() {
    return $this->squareColorFirst;
  }

  public function setBorder(array $border) {
    $this->border = $border;
    return $this;
  }

  public function setLanguageCode($language_code) {
    $this->languageCode = $language_code;
    return $this;
  }

  public function setSquareColorFirst($square_color_first) {
    $this->squareColorFirst = $square_color_first;
    return $this;
  }

}
