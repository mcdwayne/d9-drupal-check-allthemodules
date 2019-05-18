<?php

namespace Drupal\oop_forms\Form\Element;

/**
 * Class Item
 * Provides a display-only form element with an optional title and description.
 *
 */
class Item extends Element {

  /**
   * Markup string or renderable array.
   *
   * @var string|array
   */
  protected $markup;

  /**
   * Item constructor.
   *
   */
  public function __construct() {
    return parent::__construct('item');
  }

  /**
   * Gets markup.
   *
   * @return array|string
   */
  public function getMarkup() {
    return $this->markup;
  }

  /**
   * Sets markup.
   *
   * @param array|string $markup
   *
   * @return Item
   */
  public function setMarkup($markup) {
    $this->markup = $markup;

    return $this;
  }

  /**
   * {@inheritdoc}.
   */
  public function build() {
    $element = parent::build();

    Element::addParameter($element, 'markup', $this->markup);

    return $element;
  }


}
