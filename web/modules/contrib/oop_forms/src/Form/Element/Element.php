<?php

namespace Drupal\oop_forms\Form\Element;

use Drupal\Core\Entity\Entity;

class Element {

  /**
   * Element type.
   *
   * @var string
   */
  protected $type;

  /**
   * Element title.
   *
   * @var string
   */
  protected $title;

  /**
   * Default value of the element.
   *
   * @var string|array|Entity.
   */
  protected $defaultValue;

  /**
   * Element description.
   *
   * @var string.
   */
  protected $description;

  /**
   * Element required property.
   *
   * @var bool
   */
  protected $required;

  /**
   * Element disabled property.
   *
   * @var bool
   */
  protected $disabled;

  /**
   * Classes array.
   *
   * @var string[]
   */
  protected $classes = [];

  /**
   * Element constructor.
   *
   * @param string $type
   */
  public function __construct($type) {
    $this->type = $type;
  }

  /**
   * Gets element type.
   *
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Sets element type.
   *
   * @param string $type
   *
   * @return Element
   */
  public function setType($type) {
    $this->type = $type;

    return $this;
  }

  /**
   * Gets element title.
   *
   * @return string
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Sets element title.
   *
   * @param string $title
   *
   * @return Element
   */
  public function setTitle($title) {
    $this->title = $title;

    return $this;
  }

  /**
   * Gets default value.
   *
   * @return array|string|Entity
   */
  public function getDefaultValue() {
    return $this->defaultValue;
  }

  /**
   * Sets default value.
   *
   * @param array|string|Entity $defaultValue
   *
   * @return Element
   */
  public function setDefaultValue($defaultValue) {
    $this->defaultValue = $defaultValue;

    return $this;
  }

  /**
   * Gets element description.
   *
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Sets element description.
   *
   * @param string $description
   *
   * @return Element
   */
  public function setDescription($description) {
    $this->description = $description;

    return $this;
  }

  /**
   * Gets the element required property.
   *
   * @return bool
   */
  public function getRequired() {
    return $this->required;
  }

  /**
   * Sets the element required property
   *
   * @param bool $required
   *
   * @return Element
   */
  public function setRequired($required = TRUE) {
    $this->required = $required;

    return $this;
  }

  /**
   * Gets the element disabled property.
   *
   * @return mixed
   *
   */
  public function getDisabled() {
    return $this->disabled;
  }

  /**
   * Sets the element disabled property.
   *
   * @param bool $disabled
   *
   * @return Element
   */
  public function setDisabled($disabled = TRUE) {
    $this->disabled = $disabled;

    return $this;
  }

  /**
   * Adds class to the element
   * @param string $class
   *
   * @return $this
   */
  public function addClass($class) {
    $this->classes[] = $class;

    return $this;
  }

  /**
   * Gets classes for the element.
   *
   * @return string[]
   */
  public function getClasses() {
    return $this->classes;
  }

  /**
   * Adds parameter to the form array making sure it's not empty.
   *
   * @param array  $form
   * @param string $name
   * @param mixed  $value
   *
   * @return array
   */
  static protected function addParameter(&$form, $name, $value) {
    if (!empty($value)) {
      $form['#' . $name] = $value;
    }
  }

  /**
   * Builds FAPI array for the element.
   *
   * @return array
   */
  public function build() {
    $form = [];

    Element::addParameter($form, 'type', $this->type);
    Element::addParameter($form, 'title', $this->title);
    Element::addParameter($form, 'default_value', $this->defaultValue);
    Element::addParameter($form, 'description', $this->description);
    Element::addParameter($form, 'required', $this->required);
    Element::addParameter($form, 'disabled', $this->disabled);

    if (!empty($this->classes)) {
      $attributes = array('class' => $this->classes);
      $form['#attributes'] = $attributes;
    }

    return $form;
  }


}
