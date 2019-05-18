<?php

namespace Drupal\plus\Utility;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\ToStringTrait;

/**
 * Defines the base class for an attribute type.
 *
 * @see \Drupal\Core\Template\Attribute
 */
abstract class AttributeBase extends ArrayObject {

  use ToStringTrait;

  /**
   * Renders '$name=""' if $value is an empty string.
   *
   * @see \Drupal\Core\Template\AttributeValueBase::render()
   */
  const RENDER_EMPTY_ATTRIBUTE = TRUE;

  /**
   * The name of the attribute.
   *
   * @var mixed
   */
  protected $name;

  /**
   * AttributeValueBase constructor.
   *
   * @param string $name
   *   The name of the attribute.
   * @param mixed $value
   *   The attribute value, passed by reference.
   */
  public function __construct($name, &$value = NULL) {
    parent::__construct($value);
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function __toString();

  /**
   * Retrieves the name of the attribute.
   *
   * @return string
   *   The attribute name.
   */
  public function name() {
    return $this->name;
  }

  /**
   * Returns a string representation of the attribute.
   *
   * While __toString only returns the value in a string form, render()
   * contains the name of the attribute as well.
   *
   * @return string
   *   The string representation of the attribute.
   */
  public function render() {
    $value = (string) $this;
    if (isset($this->__storage) && static::RENDER_EMPTY_ATTRIBUTE || !empty($value)) {
      return Html::escape($this->name()) . '="' . $value . '"';
    }
    return '';
  }

  /**
   * Sets the name of the attribute.
   *
   * @param string $name
   *   The name of the attribute.
   *
   * @return static
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

}
