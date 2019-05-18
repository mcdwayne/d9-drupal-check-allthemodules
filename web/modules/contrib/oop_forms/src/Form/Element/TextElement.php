<?php

namespace Drupal\oop_forms\Form\Element;

class TextElement extends Element {

  /**
   * Size of the element.
   *
   * @var int
   */
  protected $size;

  /**
   * Max length of the element.
   *
   * @var int
   */
  protected $maxLength;

  /**
   * Gets element size.
   *
   * @return int
   */
  public function getSize() {
    return $this->size;
  }

  /**
   * Sets element size.
   *
   * @param int $size
   *
   * @return TextElement
   */
  public function setSize($size) {
    $this->size = $size;

    return $this;
  }

  /**
   * Gets element's max length.
   *
   * @return int
   */
  public function getMaxLength() {
    return $this->maxLength;
  }

  /**
   * Sets element's max length.
   *
   * @param int $maxLength
   *
   * @return TextElement
   */
  public function setMaxLength($maxLength) {
    $this->maxLength = $maxLength;

    return $this;
  }

  /**
   * {@inheritdoc}.
   */
  public function build() {
    $form = parent::build();

    if (!empty($this->size)) {
      $form['#size'] = $this->size;
    }

    if (!empty($this->maxLength)) {
      $form['#maxlength'] = $this->maxLength;
    }

    return $form;
  }


}
