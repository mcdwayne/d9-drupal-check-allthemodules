<?php

namespace Drupal\easymeta;

/**
 * Class to describes Metas.
 */
class MetaType {
  protected $id;
  protected $name;
  protected $label;
  protected $fieldType;
  protected $meta;
  protected $property;
  protected $isTitle;
  protected $options;
  protected $defaultValue;
  protected $tag;
  protected $nameProperty;

  /**
   * Get Id.
   *
   * @return mixed
   *   MetaType Id.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Set MetaType Id.
   *
   * @param mixed $id
   *   MetaType Id.
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * Get MetaType name.
   *
   * @return mixed
   *   MetaType Name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Set MetaType name.
   *
   * @param mixed $name
   *   MetaType Name.
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * Get MetaType label.
   *
   * @return mixed
   *   MetaType Label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Set MetaType label.
   *
   * @param mixed $label
   *   MetaType Label.
   */
  public function setLabel($label) {
    $this->label = $label;
  }

  /**
   * Get MetaType Field type.
   *
   * @return mixed
   *   MetaType Field Type.
   */
  public function getFieldType() {
    return $this->fieldType;
  }

  /**
   * Set MetaType FieldType.
   *
   * @param mixed $field_type
   *   MetaType Field Type.
   */
  public function setFieldType($field_type) {
    $this->fieldType = $field_type;
  }

  /**
   * Get MetaType Meta.
   *
   * @return Meta
   *   MetaType Meta.
   */
  public function getMeta() {
    return $this->meta;
  }

  /**
   * Set MetaType Meta.
   *
   * @param Meta $meta
   *   MetaType Meta.
   */
  public function setMeta(Meta $meta) {
    $this->meta = $meta;
  }

  /**
   * Get MetaType Property.
   *
   * @return mixed
   *   MetaType Property.
   */
  public function getProperty() {
    return $this->property;
  }

  /**
   * Set MetaType Property.
   *
   * @param mixed $property
   *   MetaType Property.
   */
  public function setProperty($property) {
    $this->property = $property;
  }

  /**
   * Get MetaType IsTitle. Boolean to indicate if this is the title Meta.
   *
   * @return mixed
   *   MetaType IsTitle.
   */
  public function getIsTitle() {
    return $this->isTitle;
  }

  /**
   * Set MetaType IsTitle. Boolean to indicate if this is the title Meta.
   *
   * @param mixed $is_title
   *   MetaType IsTitle.
   */
  public function setIsTitle($is_title) {
    $this->isTitle = $is_title;
  }

  /**
   * Get MetaType Options. Used for the select form element.
   *
   * @return mixed
   *   MetaType options.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Set MetaType Options. Used for the select form element.
   *
   * @param mixed $options
   *   MetaType options.
   */
  public function setOptions($options) {
    $this->options = $options;
  }

  /**
   * Get MetaType default value for the form.
   *
   * @return mixed
   *   MetaType Default value.
   */
  public function getDefaultValue() {
    return $this->defaultValue;
  }

  /**
   * Set MetaType default value for the form.
   *
   * @param mixed $default_value
   *   MetaType Default value.
   */
  public function setDefaultValue($default_value) {
    $this->defaultValue = $default_value;
  }

  /**
   * Get MetaType Tag.
   *
   * @return mixed
   *   MetaType Tag.
   */
  public function getTag() {
    return $this->tag;
  }

  /**
   * Set MetaType Tag.
   *
   * @param mixed $tag
   *   MetaType Tag.
   */
  public function setTag($tag) {
    $this->tag = $tag;
  }

  /**
   * Get MetaType NameProperty.
   *
   * @return mixed
   *   MetaType NameProperty.
   */
  public function getNameProperty() {
    return $this->nameProperty;
  }

  /**
   * Set MetaType NameProperty.
   *
   * @param mixed $name_property
   *   MetaType NameProperty.
   */
  public function setNameProperty($name_property) {
    $this->nameProperty = $name_property;
  }

}
